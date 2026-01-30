<?php

namespace App\Http\Controllers\admin;

use App\Exports\AttendanceExport;
use Illuminate\Http\Request;
use App\Models\StudentAttendance;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\StudentEventRegistration;
use Exception;
use Maatwebsite\Excel\Facades\Excel;

class StudentAttendanceController extends Controller
{
    public function index()
    {
        $this->data['events'] = Event::with('get_club')->get();
        return view('admin.student_attendance_index')->with($this->data);
    }

    public function attendanceEntry(Request $request)
    {
        $eventId = $request->event_id;
        $this->data['event'] = Event::findOrFail($eventId);
        $this->data['get_schedule_event'] = EventSchedule::with('department')
            ->where('event_id', $eventId)
            ->distinct('department_id')
            ->get(['department_id', 'event_id']);
            $this->data['registeredStudents'] = collect();
            if ($request->filled('department_id') && $request->filled('event_date')) {
            $schedule = EventSchedule::where('event_id', $eventId)
                ->where('department_id', $request->department_id)
                ->whereDate('event_date', $request->event_date)
                ->first();
                if ($schedule) {
                $this->data['attendance_entry'] = StudentAttendance::where('event_id', $eventId)
                ->where('event_schedule_id', $schedule->id)->get();
                $this->data['registeredStudents'] = StudentEventRegistration::with([
                    'student.get_department'
                ])
                    ->where('event_id', $eventId)
                    ->where('event_schedule_id', $schedule->id)
                    ->get();
            }else{
                $this->data['attendance_entry'] = collect();
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
        try{
        
            $request->validate([
                'event_id'   => 'required|exists:events,id',
                'attendance' => 'required|array',
            ]);

            $eventId = $request->event_id;

            $eventSchedule = EventSchedule::where([
                'event_id'      => $eventId,
                'department_id' => $request->department_id,
                'event_date'    => $request->event_date
            ])->first();
            if (!$eventSchedule) {
                return redirect()->back()->with('error', 'No schedule found for the selected event, department, and date.');
            }
            foreach ($request->attendance as $studentId => $data) {

                $existingAttendance = StudentAttendance::where('event_id', $eventId)
                    ->where('student_id', $studentId)
                    ->where('event_schedule_id' ,  $eventSchedule->id)
                    ->first();

                if ($existingAttendance) {
                    $attendance = $existingAttendance;
                } else {
                    $attendance = new StudentAttendance();
                    $attendance->event_id = $eventId;
                    $attendance->event_schedule_id = $eventSchedule->id;
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
                        'event_id'   => $eventId,
                        'student_id' => $studentId,
                    ])->update([
                        'status' => 3,
                    ]);
                }
        }
        } catch (Exception $e) {
            echo '<pre>';
            print_r($e->getMessage());
            echo '</pre>';
            exit;
            return $e->getMessage();
        }

        return redirect()->back()->with('success', 'Attendance submitted successfully');
    }
}
