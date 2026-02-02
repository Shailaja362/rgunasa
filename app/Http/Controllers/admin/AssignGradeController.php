<?php

namespace App\Http\Controllers\admin;

use App\Models\Event;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Models\StudentFeedback;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\StudentAttendance;
use App\Models\StudentUploadProof;
use App\Http\Controllers\Controller;
use App\Models\EventSchedule;
use App\Models\StudentEventRegistration;
use App\Traits\ResolvesEventSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class AssignGradeController extends Controller
{
    use ResolvesEventSchedule;

    public function index()
    {
        $this->data['events'] = Event::with('get_club')->get();
        return view('admin.assign_grade_index')->with($this->data);
    }

    public function gradeEntry(Request $request)
    {
        $eventId = $request->event_id;
        $this->data['event'] = Event::findOrFail($eventId);
        $this->data['registrations'] = collect();
        $this->data['schedule_department'] = EventSchedule::with('department')
            ->where('event_id', $eventId)
            ->get()
            ->groupBy('department_id');
        if ($request->filled('department_id') && $request->filled('event_date')) {
            $schedule = $this->resolveSchedule(
                $eventId,
                $request->department_id,
                $request->event_date
            );
            if ($schedule) {
                $this->data['registrations'] = StudentAttendance::with('student.get_department')
                    ->where('event_id', $eventId)
                    ->where('event_schedule_id', $schedule->id)
                    ->whereNotNull('entry_time')
                    ->whereNotNull('exit_time')
                    ->get();
            }
        }
        return view('admin.assign_grade_entry')->with($this->data);
    }

    public function saveGrades(Request $request)
    {
        $request->validate([
            'event_id'    => 'required|exists:events,id',
            'schedule_id' => 'required|exists:event_schedules,id',
            'grades'      => 'required|array'
        ]);

        DB::beginTransaction();

        try {

            foreach ($request->grades as $studentId => $grade) {

                $updated = StudentEventRegistration::where([
                    'event_id' => $request->event_id,
                    'student_id' => $studentId,
                    'event_schedule_id' => $request->schedule_id
                ])->update([
                    'grade'  => $grade,
                    'status' => 2
                ]);

                if (!$updated) {
                    throw new \Exception("Registration not found for student ID: {$studentId}");
                }
            }

            DB::commit();

            return back()->with('success', 'Grades saved successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Grade Save Failed', ['error' => $e->getMessage()]);
            return back()->with('error', $e->getMessage());
        }
    }

    public function downloadEventReport(Request $request)
    {
        $student = Student::findOrFail($request->student);
        $event   = Event::findOrFail($request->event);
        $event_schedule   = EventSchedule::findOrFail($request->schedule_id);
        $proofs = StudentUploadProof::where([
            'student_id' => $request->student,
            'event_id'   => $request->event,
            'event_schedule_id' => $request->schedule_id,
        ])->get();

        $feedback = StudentFeedback::where([
            'student_id' => $request->student,
            'event_id'   => $request->event,
            'event_schedule_id' => $request->schedule_id,
        ])->firstOrFail();

        $pdf = Pdf::loadView('student.pdf.student_event_report', compact(
            'student',
            'event',
            'proofs',
            'feedback',
            'event_schedule'
        ))->setPaper('a4', 'portrait');

        return $pdf->stream('event-report.pdf');
    }

    public function downloadAll($eventId, $studentId, $scheduleId)
    {
        $proofs = StudentUploadProof::where('event_id', $eventId)
            ->where('student_id', $studentId)
            ->where('event_schedule_id', $scheduleId)
            ->get();

        // Filter only document files
        $docFiles = $proofs->filter(function ($file) {
            $ext = strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION));
            return in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'rtf']);
        });

        if ($docFiles->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No document files to download.'
            ]);
        }

        $files = $docFiles->map(function ($file) {
            return [
                'name' => $file->file_name,
                'url' => asset('storage/' . $file->file_path)
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'files' => $files
        ]);
    }
}
