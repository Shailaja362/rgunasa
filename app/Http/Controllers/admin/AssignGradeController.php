<?php

namespace App\Http\Controllers\admin;

use App\Models\Event;
use Illuminate\Http\Request;
use App\Models\StudentAttendance;
use App\Http\Controllers\Controller;
use App\Models\StudentEventRegistration;

class AssignGradeController extends Controller
{
    public function index()
    {

        $this->data['events'] = Event::with('get_club')->get();
        return view('admin.assign_grade_index')->with($this->data);
    }

    public function gradeEntry(Request $request)
    {
        $this->data['registrations'] = StudentAttendance::with('student', 'get_grade', 'student.get_department')
            ->whereNotNull('entry_time')
            ->whereNotNull('exit_time')
            ->where('event_id', $request->event_id)
            ->orderBy('id')
            ->get();
        $this->data['event'] = Event::find($request->event_id);
        $this->data['attendance_entry'] = StudentAttendance::where('event_id', $request->event_id)->get();
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
                ->first();
            if ($registration) {
                $registration->grade = $grade;
                $registration->status = 3;
                $registration->save();
            }
        }

        return redirect()->route('assign_grade_entry')->with('success', 'Grades assigned successfully.');
    }
}
