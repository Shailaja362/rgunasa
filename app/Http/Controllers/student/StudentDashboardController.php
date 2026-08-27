<?php

namespace App\Http\Controllers\student;

use App\Http\Controllers\Controller;
use App\Models\CreditPoint;
use App\Models\Event;
use App\Models\Student;
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
        $this->data['student'] = $student;
        $this->data['studentId'] = $student->id;
        $this->data['events'] = Event::where([
            'publish' => 1,
            'is_active' => 'y'
        ])->get();
        // Student's registrations
        $studentRegistrations = StudentEventRegistration::with('event', 'schedule')
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })
            ->where('student_id', $student->id)
            ->get();
        $this->data['studentRegistrations'] = $studentRegistrations;
        $this->data['completed_events'] = StudentEventRegistration::with('get_event_attendance', 'event')->where('student_id', $student->id)
            ->whereHas('get_event_attendance', function ($query) use ($now) {
                $query->whereNotNull('entry_time')
                    ->whereNotNull('exit_time');
            })
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })
            ->get();
        $this->data['certificate_earned'] = $studentRegistrations
            ->whereNotNull('grade');
        // Upcoming and ongoing department-wise events
        $this->data['ongoingEvents'] = Event::whereHas('get_dep_events', function ($q) use ($student) {
            $q->where('programme_id', $student->programme_id)
                ->where('section', $student->section)
                ->openToBatch($student->batch)
                ->openToSemester($student->semester)
                ->where('event_date', Carbon::now()->toDateString());
        })
            ->with(['get_dep_events' => function ($q) use ($student) {
                $q->where('programme_id', $student->programme_id)
                    ->where('section', $student->section)
                    ->openToBatch($student->batch)
                    ->openToSemester($student->semester)
                    ->where('event_date', Carbon::now()->toDateString());
            }, 'get_dep_events.registrations'])
            ->where('publish', 1)
            ->get();

        $this->data['registeredEvents'] = StudentEventRegistration::with('event', 'get_event_schedule', 'student')
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })
            ->where('student_id', $student->id)
            ->get();
        $this->data['upcomingEvents'] = Event::whereHas('get_dep_events', function ($q) use ($student) {
            $q->where('programme_id', $student->programme_id)
                ->where('section', $student->section)
                ->openToBatch($student->batch)
                ->openToSemester($student->semester)
                ->where('event_date', '>=', Carbon::now()->toDateString()); // Only future dates
        })
            ->with(['get_dep_events' => function ($q) use ($student) {
                $q->where('programme_id', $student->programme_id)
                    ->where('section', $student->section)
                    ->openToBatch($student->batch)
                    ->openToSemester($student->semester)
                    ->where('event_date', '>=', Carbon::now()->toDateString())
                    ->orderBy('event_date', 'asc');
            }, 'get_dep_events.registrations'])
            ->where([
                'publish' => 1,
                'is_active' => 'y'
            ])
            ->get();

        $this->data['studentRegistrations'] = StudentEventRegistration::whereHas('schedule', function ($q) use ($student) {
            $q->where('programme_id', $student->programme_id)
                ->where('section', $student->section)
                ->openToBatch($student->batch)
                ->openToSemester($student->semester)
                ->where('event_date', '>', Carbon::now()->toDateString()); // Only future dates
        })
            ->with(['schedule' => function ($q) use ($student) {
                $q->where('programme_id', $student->programme_id)
                    ->where('section', $student->section)
                    ->openToBatch($student->batch)
                    ->openToSemester($student->semester)
                    ->where('event_date', '>', Carbon::now()->toDateString())
                    ->orderBy('event_date', 'asc');
                // ->orderBy('start_time', 'asc');
            }, 'schedule.registrations'])->where('student_id', $student->id)
            ->with('event')
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })
            ->get();
        $this->data['registered_count'] = StudentEventRegistration::with('event')
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })->where('student_id', $student->id)->get();
        $this->data['config_credit'] = CreditPoint::where('semester', $student->semester)->first();

        $earnedCredits = StudentEventRegistration::with('event')->where('student_id', $student->id)
            ->whereNotNull('grade')
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })
            ->where('grade', '!=', 'd')
            ->whereHas('get_event_schedule', function ($q) use ($student) {
                $q->openToSemester($student->semester)
                    ->openToBatch($student->batch)
                    ->where('programme_id', $student->programme_id);
            })
            ->with('get_event_schedule:id,credit_points')
            ->get()
            ->sum(function ($reg) {
                return $reg->get_event_schedule->credit_points ?? 0;
            });

        if ($earnedCredits > 4) {
            $earned = 4;
        } else {
            $earned =  $earnedCredits;
        }
        $this->data['earned_credit'] = $earned;
        $this->data['pending_credit'] =  $this->data['config_credit']?->credit_points - $earned;
        return view('student.student_dashboard')->with($this->data);
    }
}
