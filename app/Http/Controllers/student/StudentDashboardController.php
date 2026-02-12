<?php

namespace App\Http\Controllers\student;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\StudentEventRegistration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        $student = session()->get('student');
        $this->data['studentId'] = $student->id;
        $this->data['events'] = Event::get();
        // Student's registrations
        $studentRegistrations = StudentEventRegistration::with('event', 'schedule')
            ->where('student_id', $student->id)
            ->get();
        $this->data['studentRegistrations'] = $studentRegistrations;

        $this->data['completed_events'] = StudentEventRegistration::with('get_event_attendance')->where('student_id', $student->id)
            ->whereHas('get_event_attendance', function ($query) use ($now) {
                $query->whereNotNull('entry_time')
                    ->whereNotNull('exit_time');
            })
            ->where('status', 3)
            ->get();

        $this->data['certificate_earned'] = $studentRegistrations
            ->where('status', 2)
            ->whereNotNull('grade');

        // Upcoming and ongoing department-wise events
        $this->data['ongoingEvents'] = Event::whereHas('get_dep_events', function ($q) use ($student) {
            $q->where('programme_id', $student->programme_id)
                ->where('event_date', Carbon::now()->toDateString());
        })
            ->with(['get_dep_events' => function ($q) use ($student) {
                $q->where('programme_id', $student->programme_id)
                    ->where('event_date', Carbon::now()->toDateString());
            }, 'get_dep_events.registrations'])
            ->get();

        $this->data['registeredEvents'] = StudentEventRegistration::with('event', 'get_event_schedule', 'student')
            ->where('student_id', $student->id)
            ->get();
        $this->data['upcomingEvents'] = Event::whereHas('get_dep_events', function ($q) use ($student) {
            $q->where('programme_id', $student->programme_id)
                ->where('event_date', '>=', Carbon::now()->toDateString()); // Only future dates
        })
            ->with(['get_dep_events' => function ($q) use ($student) {
                $q->where('programme_id', $student->programme_id)
                    ->where('event_date', '>=', Carbon::now()->toDateString())
                    ->orderBy('event_date', 'asc');
            }, 'get_dep_events.registrations'])
            ->get();

        $this->data['studentRegistrations'] = StudentEventRegistration::whereHas('schedule', function ($q) use ($student) {
            $q->where('programme_id', $student->programme_id)
                ->where('event_date', '>', Carbon::now()->toDateString()); // Only future dates
        })
            ->with(['schedule' => function ($q) use ($student) {
                $q->where('programme_id', $student->programme_id)
                    ->where('event_date', '>', Carbon::now()->toDateString())
                    ->orderBy('event_date', 'asc');
                // ->orderBy('start_time', 'asc');
            }, 'schedule.registrations'])->where('student_id', $student->id)
            ->with('event') // eager load event for date and type
            ->get();
        $this->data['registered_count'] = StudentEventRegistration::where('student_id', $student->id)->get();

        return view('student.student_dashboard')->with($this->data);
    }
}
