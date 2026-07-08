<?php

namespace App\Http\Controllers\admin;

use App\Exports\AttendanceExport;
use Illuminate\Http\Request;
use App\Models\StudentAttendance;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\StudentEventRegistration;
use App\Traits\ResolvesEventSchedule;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class StudentAttendanceController extends Controller
{

    use ResolvesEventSchedule;

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
        $this->data['get_schedule_event'] = EventSchedule::with('programme')
            ->where('event_id', $eventId)
            ->distinct('programme_id')
            ->get(['programme_id', 'event_id']);
        if ($request->filled('programme_id') && $request->filled('event_date')) {
            $schedules = $this->resolveSchedule(
                $eventId,
                $request->programme_id,
                $request->event_date,
                $request->section,
                $request->batch,
                $request->semester
            );

            if (!empty($schedules)) {
                $this->data['attendance_entry'] = StudentAttendance::where('event_id', $eventId)
                    ->where('event_schedule_id', $schedules->id)
                    ->get();

                $this->data['registeredStudents'] =
                    StudentEventRegistration::with('student.get_department', 'student.get_programme')
                    ->where('event_id', $eventId)
                    ->where('event_schedule_id', $schedules->id)
                    ->get();
            }
        }
        return view('admin.student_attendance_entry')->with($this->data);
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
            'programme_id' => 'required',
            'event_date'    => 'required|date',
            'attendance'    => 'required|array'
        ]);
        DB::beginTransaction();
        try {
            $schedule = $this->resolveSchedule(
                $request->event_id,
                $request->programme_id,
                $request->event_date,
                $request->section,
                $request->batch,
                $request->semester
            );

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

    private function resolveSchedule($eventId, $programmeId, $date,$section,$batch,$semester)
    {

        return EventSchedule::where('event_id', $eventId)
            ->where('programme_id', $programmeId)
            ->where('section', $section)
            ->where('batch', $batch)
            ->where('semester', $semester)
//            ->where(function ($q) use ($date) {
//                $q->whereDate('event_date', $date);
//            })
            ->first();
    }
}
