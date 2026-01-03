<?php

namespace App\Http\Controllers\admin;

use App\Exports\AttendanceExport;
use Illuminate\Http\Request;
use App\Models\StudentAttendance;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\StudentEventRegistration;
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
        $this->data['registeredStudents'] = StudentEventRegistration::with('event', 'student.get_department', 'get_student_proof')
            ->where('event_id', $request->event_id)->get();
        $this->data['event'] = Event::find($request->event_id);
        $this->data['attendance_entry'] = StudentAttendance::where('event_id', $request->event_id)->get();
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
            'event_id'   => 'required|exists:events,id',
            'attendance' => 'required|array',
        ]);

        $eventId = $request->event_id;

        foreach ($request->attendance as $studentId => $data) {
            // Use firstOrNew to create or update attendance
            $existingAttendance = StudentAttendance::where('event_id', $eventId)
                ->where('student_id', $studentId)
                ->first();

            if ($existingAttendance) {
                $attendance = $existingAttendance;
            } else {
                $attendance = new StudentAttendance();
                $attendance->event_id = $eventId;
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



        return redirect()->back()->with('success', 'Attendance submitted successfully');
    }
}
