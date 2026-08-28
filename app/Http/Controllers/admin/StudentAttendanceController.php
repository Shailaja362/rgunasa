<?php

namespace App\Http\Controllers\admin;

use App\Exports\AttendanceExport;
use Illuminate\Http\Request;
use App\Models\StudentAttendance;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Programme;
use App\Models\StudentEventRegistration;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class StudentAttendanceController extends Controller
{
    public function index()
    {
        $adminId = Auth::guard('admin')->id();
        if (!empty(session()->get('super_admin'))) {
            $this->data['events'] = Event::with('get_club')
                ->where([
                'publish' => 1 ,
                'is_active' => 'y'
                ])
                ->paginate(10);
        } else {
            $this->data['events'] = Event::with('get_club')
                ->where('created_by', $adminId)
                ->where([
                'publish' => 1,
                'is_active' => 'y'
                ])
                ->paginate(10);
        }
        return view('admin.student_attendance_index')->with($this->data);
    }

    public function attendanceEntry(Request $request)
    {
        $eventId = $request->event_id;

        $this->data['event'] = Event::findOrFail($eventId);
        $this->data['registeredStudents'] = collect();
        $this->data['attendance_entry'] = collect();
        $this->data['schedule'] = null;
        $scheduleDepartment = $this->groupSchedulesByProgramme($eventId);
        $this->data['get_schedule_event'] = Programme::whereIn('id', $scheduleDepartment->keys())->get();
        $this->data['programmeScheduleOptions'] = $this->buildProgrammeScheduleOptions($scheduleDepartment);

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
                $this->data['schedule'] = $schedule;

                $this->data['attendance_entry'] = StudentAttendance::where('event_id', $eventId)
                    ->where('event_schedule_id', $schedule->id)
                    ->when(!empty($batches), fn($q) => $q->whereHas('student', fn($sq) => $sq->whereIn('batch', $batches)))
                    ->when(!empty($semesters), fn($q) => $q->whereHas('student', fn($sq) => $sq->whereIn('semester', $semesters)))
                    ->get();

                $this->data['registeredStudents'] =
                    StudentEventRegistration::with('student.get_department', 'student.get_programme')
                    ->where('event_id', $eventId)
                    ->where('event_schedule_id', $schedule->id)
                    ->when(!empty($batches), fn($q) => $q->whereHas('student', fn($sq) => $sq->whereIn('batch', $batches)))
                    ->when(!empty($semesters), fn($q) => $q->whereHas('student', fn($sq) => $sq->whereIn('semester', $semesters)))
                    ->get();
            }
        }
        return view('admin.student_attendance_entry')->with($this->data);
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

    public function download(Request $request)
    {
        $event_id = Event::where('id', $request->event_id)->first();
        $fileName = $event_id->title . '_' . 'student_attendance_' . date('Y-m-d') . '.xlsx';
        return Excel::download(new AttendanceExport($event_id->id), $fileName);
    }

    public function markAttendance(Request $request)
    {
        $request->validate([
            'event_id'      => 'required|exists:events,id',
            'schedule_id'   => 'required|exists:event_schedules,id',
            'attendance'    => 'required|array'
        ]);
        DB::beginTransaction();
        try {
            $schedule = EventSchedule::find($request->schedule_id);

            if (empty($schedule)) {
                throw new Exception('Schedule not found');
            }
                foreach ($request->attendance as $studentId => $data) {
                    $attendance = StudentAttendance::firstOrNew([
                        'event_id' => $request->event_id,
                        'student_id' => $studentId,
                        'event_schedule_id' => $schedule->id
                    ]);

                    if ($data['entry'] == 1) {
                        if (!$attendance->entry_time) {
                            $attendance->entry_time = now();
                        }
                    } else {
                        $attendance->entry_time = null;
                    }

                    if ($data['exit'] == 1) {
                        if (!$attendance->exit_time) {
                            $attendance->exit_time = now();
                        }
                    } else {
                        $attendance->exit_time = null;
                    }

                    $attendance->save();

                    StudentEventRegistration::where([
                        'event_id' => $request->event_id,
                        'student_id' => $studentId,
                        'event_schedule_id' => $schedule->id
                    ])->update([
                        'status' => ($attendance->entry_time && $attendance->exit_time) ? 3 : 2
                    ]);
                }
            DB::commit();
            return back()->with('success', 'Attendance saved successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

}
