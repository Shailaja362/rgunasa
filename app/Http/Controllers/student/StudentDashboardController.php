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
        $this->data['registered_count'] = StudentEventRegistration::where('student_id', $student->id)->get();
        $this->data['completed_events'] = StudentEventRegistration::with('get_event_attendance')->where('student_id', $student->id)
            ->whereHas('get_event_attendance', function ($query) use ($now) {
                $query->whereNotNull('entry_time')
                    ->whereNotNull('exit_time');
            })
            ->where('status', 3)
            ->get();

        $this->data['certificate_earned'] = StudentEventRegistration::with('get_event_attendance')->where('student_id', $student->id)
            ->whereNotNull('grade')
            ->where('status', 2)
            ->get();
            
        $this->data['ongoingEvents'] = Event::with('registrations')
            ->whereDate('event_date', $now->toDateString())
            // ->whereTime('start_time', '<=', $now->toTimeString())
            // ->whereTime('end_time', '>=', $now->toTimeString())
            ->orderBy('start_time', 'asc')
            ->get();

        // Upcoming Events
        $this->data['upcomingEvents'] = Event::with('registrations')
            ->where(function ($query) use ($now) {
                $query->whereDate('event_date', '>', $now->toDateString())
                    ->orWhere(function ($q) use ($now) {
                        $q->whereDate('event_date', '=', $now->toDateString())
                            ->whereTime('start_time', '>', $now->toTimeString());
                    });
            })
            ->orderBy('event_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();
        $this->data['registeredEvents'] = StudentEventRegistration::with('event')->where('student_id', $student->id)
            ->get();
        $this->data['studentRegistrations'] = \App\Models\StudentEventRegistration::where('student_id', $student->id)
            ->with('event') // eager load event for date and type
            ->get();
        return view('student.student_dashboard')->with($this->data);
    }
}
