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
            $this->data['events'] = Event::with('get_club')->paginate(10);
        } else {
            $this->data['events'] = Event::with('get_club')
                ->where('created_by', $adminId)->paginate(10);
        }
        return view('admin.student_attendance_index')->with($this->data);
    }

    public function attendanceEntry(Request $request)
    {
        $eventId = $request->event_id;

        $this->data['event'] = Event::findOrFail($eventId);
        $this->data['registeredStudents'] = collect();
        $this->data['attendance_entry'] = collect();
        $this->data['get_schedule_event'] = EventSchedule::with('department')
                ->where('event_id', $eventId)
                ->distinct('department_id')
                ->get(['department_id', 'event_id']);
        if ($request->filled('department_id') && $request->filled('event_date')) {
            $schedule = $this->resolveSchedule(
                $eventId,
                $request->department_id,
                $request->event_date
            );

            if ($schedule) {
                $this->data['attendance_entry'] = StudentAttendance::where([
                    'event_id' => $eventId,
                    'event_schedule_id' => $schedule->id
                ])->get();

                $this->data['registeredStudents'] = StudentEventRegistration::with('student.get_department')
                    ->where('event_id', $eventId)
                    ->where('event_schedule_id', $schedule->id)
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
            'event_id'     => 'required|exists:events,id',
            'department_id' => 'required',
            'event_date'   => 'required|date',
            'attendance'   => 'required|array'
        ]);
        DB::beginTransaction();
        try {
            $schedule = $this->resolveSchedule(
                $request->event_id,
                $request->department_id,
                $request->event_date
            );

            if (!$schedule) {
                throw new \Exception('Schedule not found');
            }
            foreach ($request->attendance as $studentId => $data) {
                $existingAttendance = StudentAttendance::where('event_id', $request->event_id)
                                ->where('student_id', $studentId)
                                ->where('event_schedule_id' ,  $schedule->id)
                                ->first();

                            if ($existingAttendance) {
                                $attendance = $existingAttendance;
                            } else {
                                $attendance = new StudentAttendance();
                                $attendance->event_id = $request->event_id;
                                $attendance->event_schedule_id = $schedule->id;
                                $attendance->student_id = $studentId;
                            }

                            if (!empty($data['entry']) && !$attendance->entry_time) {
                                $attendance->entry_time = now();
                            }
                            if (!empty($data['exit']) && !$attendance->exit_time) {
                                $attendance->exit_time = now();
                            }
                            $attendance->save();

                if ($attendance->entry_time && $attendance->exit_time) {
                    StudentEventRegistration::where([
                        'event_id' => $request->event_id,
                        'student_id' => $studentId,
                        'event_schedule_id' => $schedule->id
                    ])->update(['status' => 3]);
                }
            }
            DB::commit();
            return back()->with('success', 'Attendance saved successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Attendance Save Failed', ['error' => $e->getMessage()]);
            return back()->with('error', $e->getMessage());
        }
    }

    private function resolveSchedule($eventId, $departmentId, $date)
    {
        return EventSchedule::where('event_id', $eventId)
            ->where('department_id', $departmentId)
            ->where(function ($q) use ($date) {
                $q->whereDate('event_date', $date);
            })
            ->first();
    }
}
