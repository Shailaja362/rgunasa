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
use ZipArchive;

class AssignGradeController extends Controller
{
    public function index()
    {
        $this->data['events'] = Event::with('get_club')->get();
        return view('admin.assign_grade_index')->with($this->data);
    }

    public function gradeEntry(Request $request)
    {
        $eventId = $request->event_id;
        $this->data['event'] = Event::findOrFail($eventId);
        $this->data['get_schedule_event'] = StudentAttendance::with('student.get_department')
            ->where('event_id', $eventId)
            ->whereNotNull('entry_time')
            ->whereNotNull('exit_time')
            ->get()
            ->pluck('student.get_department', 'student_id')
            ->unique('id');
        $this->data['schedule_department'] = EventSchedule::with('department')->where('event_id', $eventId)->get();
        $this->data['registrations'] = collect();
        if ($request->filled('department_id') && $request->filled('event_date')) {
            $get_schedule_dept = EventSchedule::where(['event_id' => $request->event_id, 'event_date' => $request->event_date, 'department_id' => $request->department_id])->first();
            if ($get_schedule_dept) {
                $this->data['registrations'] = StudentAttendance::with([
                    'student.get_department',
                    'get_student_upload_proof',
                    'get_feedback',
                    'grades' => function ($q) use ($eventId) {
                        $q->where('event_id', $eventId);
                    }
                ])
                    ->where('event_id', $eventId)
                    ->whereHas('student', function ($q) use ($request) {
                        $q->where('department_id', $request->department_id);
                    })
                    ->where('event_schedule_id', $get_schedule_dept->id)
                    ->whereNotNull('entry_time')
                    ->whereNotNull('exit_time')
                    ->orderBy('id')
                    ->get();
            }
        }

        return view('admin.assign_grade_entry')->with($this->data);
    }


    public function saveGrades(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'grades'   => 'required|array',
        ]);

        $eventId = $request->event_id;

        foreach ($request->grades as $registrationId => $grade) {
            $registration = StudentEventRegistration::where('student_id', $registrationId)
                ->where('event_id', $eventId)
                ->where('event_schedule_id', $request->schedule_id)
                ->first();
            if ($registration) {
                $registration->grade = $grade;
                $registration->status = 2;
                $registration->save();
            }
        }

        return redirect()->back()->with('success', 'Grades assigned successfully.');
    }

    public function downloadEventReport(Request $request)
    {
        $student = Student::findOrFail($request->student);
        $event   = Event::findOrFail($request->event);
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
            'feedback'
        ))->setPaper('a4', 'portrait');

        return $pdf->stream('event-report.pdf');
    }

    public function downloadAll($eventId, $studentId)
    {
        $proofs = StudentUploadProof::where('event_id', $eventId)
            ->where('student_id', $studentId)
            ->where('event_schedule_id', request()->schedule_id)
            ->get();

        // Filter only document files
        $docFiles = $proofs->filter(function ($file) {
            $ext = strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION));
            return in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx']);
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
