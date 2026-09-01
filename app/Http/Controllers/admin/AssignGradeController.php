<?php

namespace App\Http\Controllers\admin;

use ZipArchive;
use App\Models\Event;
use App\Models\Programme;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Models\EventSchedule;
use App\Models\StudentFeedback;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\StudentAttendance;
use App\Models\StudentUploadProof;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\StudentEventRegistration;

class AssignGradeController extends Controller
{
    public function index()
    {
        $adminId = Auth::guard('admin')->id();
        if (!empty(session()->get('super_admin'))) {
            $this->data['events'] = Event::with('get_club')
                ->where([
                'publish' => 1,
                'is_active' => 'y'
                ])
                ->paginate(10);
        } else {
            $this->data['events'] = Event::with('get_club')
                ->where('created_by', $adminId)
                ->where([
                'publish' => 1 ,
                'is_active' => 'y'
                ])
                ->paginate(10);
        }
        return view('admin.assign_grade_index')->with($this->data);
    }

    public function gradeEntry(Request $request)
    {
        $eventId = $request->event_id;
        $this->data['event'] = Event::findOrFail($eventId);
        $this->data['registrations'] = collect();
        $this->data['gradesByStudent'] = collect();
        $this->data['schedule_department'] = $this->groupSchedulesByProgramme($eventId);
        $this->data['programmeScheduleOptions'] = $this->buildProgrammeScheduleOptions($this->data['schedule_department']);
        $this->data['programmeNames'] = Programme::whereIn('id', $this->data['schedule_department']->keys())
            ->pluck('name', 'id');

        $batches = array_filter((array) $request->batch);
        $semesters = array_filter((array) $request->semester);

        if ($request->filled('programme_id') && $request->filled('event_date')) {
            $schedule = EventSchedule::where('event_id', $eventId)
                ->openToProgramme($request->programme_id)
                ->openToSection($request->section)
                ->openToAnyBatch($batches)
                ->openToAnySemester($semesters)
                ->first();

            if ($schedule) {
                $this->data['registrations'] = StudentAttendance::with('student.get_department', 'student.get_programme')
                    ->where('event_id', $eventId)
                    ->where('event_schedule_id', $schedule->id)
                    ->whereNotNull('entry_time')
                    ->whereNotNull('exit_time')
                    ->when(!empty($batches), fn($q) => $q->whereHas('student', fn($sq) => $sq->whereIn('batch', $batches)))
                    ->when(!empty($semesters), fn($q) => $q->whereHas('student', fn($sq) => $sq->whereIn('semester', $semesters)))
                    ->get();

                $this->data['gradesByStudent'] = StudentEventRegistration::where('event_schedule_id', $schedule->id)
                    ->whereIn('student_id', $this->data['registrations']->pluck('student_id'))
                    ->pluck('grade', 'student_id');
            }
        }
        return view('admin.assign_grade_entry')->with($this->data);
    }

    /**
     * programme_id is a comma-separated list of programme ids per schedule,
     * so a schedule open to several programmes contributes to each of their
     * groups rather than being grouped by the raw CSV string.
     */
    private function groupSchedulesByProgramme($eventId)
    {
        $scheduleDepartment = collect();
        foreach (EventSchedule::where('event_id', $eventId)->get() as $schedule) {
            $ids = !empty($schedule->programme_id) ? array_map('trim', explode(',', $schedule->programme_id)) : [];
            foreach ($ids as $id) {
                $scheduleDepartment->put($id, ($scheduleDepartment->get($id) ?? collect())->push($schedule));
            }
        }
        return $scheduleDepartment;
    }

    /**
     * Batch/semester values actually used per programme in this event's
     * schedules, so the filter form only offers relevant choices.
     */
    private function buildProgrammeScheduleOptions($scheduleDepartment)
    {
        return $scheduleDepartment->map(function ($schedules) {
            $batches = $schedules->flatMap(fn($s) => explode(',', (string) $s->batch))
                ->map(fn($b) => trim($b))
                ->filter()
                ->unique()
                ->sort()
                ->values();

            $semesters = $schedules->flatMap(fn($s) => explode(',', (string) $s->semester))
                ->map(fn($s) => trim($s))
                ->filter()
                ->unique()
                ->sort()
                ->values();

            return ['batches' => $batches, 'semesters' => $semesters];
        });
    }

    public function saveGrades(Request $request)
    {
        $request->validate([
            'event_id'    => 'required|exists:events,id',
            'grades.student' => 'required|array',
            'grades.schedule' => 'required|array'
        ]);
        DB::beginTransaction();
        try {
            $grades = $request->grades['student'];
            $schedules = $request->grades['schedule'];

            foreach ($grades as $studentId => $grade){
                if (empty($grade)) {
                    continue;
                }
                $scheduleId = $schedules[$studentId] ?? null;
                if (!$scheduleId) {
                    throw new \Exception("Schedule ID missing for student ID: {$studentId}");
                }
                $updated = StudentEventRegistration::where([
                    'event_id' => $request->event_id,
                    'student_id' => $studentId,
                    'event_schedule_id' => $scheduleId
                ])->update([
                    'grade'  => $grade
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
        $student = Student::with('get_department')->findOrFail($request->student);
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
