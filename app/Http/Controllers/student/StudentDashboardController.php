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
        $isFirstYearStudent = in_array((int) $student->semester, [1, 2]);
        $today = Carbon::now()->toDateString();

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
            ->where('status', 3)
            ->get();
        $this->data['certificate_earned'] = $studentRegistrations
            ->where('status', 2)
            ->whereNotNull('grade');

        $this->data['ongoingEvents'] = Event::whereHas('get_dep_events', function ($q) use ($student, $isFirstYearStudent) {
            $q->whereDate('event_date', Carbon::now()->toDateString());
            $this->applyStudentScheduleFilter($q, $student, $isFirstYearStudent);
        })
            ->with([
                'get_dep_events' => function ($q) use ($student, $isFirstYearStudent) {
                    $q->whereDate('event_date', Carbon::now()->toDateString());
                    $this->applyStudentScheduleFilter($q, $student, $isFirstYearStudent);
                },
                'get_dep_events.registrations'
            ])
            ->where('publish', 1)
            ->get();

        $this->data['registeredEvents'] = StudentEventRegistration::with('event', 'get_event_schedule', 'student')
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })
            ->where('student_id', $student->id)
            ->get();
        $this->data['upcomingEvents'] = Event::whereHas('get_dep_events', function ($q) use ($student, $isFirstYearStudent) {
            $this->applyStudentScheduleFilter($q, $student, $isFirstYearStudent);
        })
            ->with([
                'get_dep_events' => function ($q) use ($student, $isFirstYearStudent) {
                    $this->applyStudentScheduleFilter($q, $student, $isFirstYearStudent);
                    $q->orderBy('event_date', 'asc');
                },
                'get_dep_events.registrations'
            ])
            ->where([
                'publish' => 1,
                'is_active' => 'y'
            ])
            ->get();

        $this->data['studentRegistrations'] = StudentEventRegistration::where('student_id', $student->id)
            ->whereHas('schedule', function ($q) use ($student, $isFirstYearStudent) {
                $q->whereDate('event_date', '>', Carbon::now()->toDateString())
                    ->where(function ($subQ) use ($student, $isFirstYearStudent) {
                        // Normal events
                        $subQ->where(function ($normalQ) use ($student) {
                            $normalQ->where('programme_id', $student->programme_id)
                                ->where('section', $student->section)
                                ->where('batch', $student->batch)
                                ->where('semester', $student->semester);
                        });
                        // First year common events
                        if ($isFirstYearStudent) {
                            $subQ->orWhere(function ($firstYearQ)  use ($student) {
                                $firstYearQ->whereNull('programme_id')
                                    ->whereNull('section')
                                ->where(function ($batchQ) use ($student) {
                                    $batchQ->whereNull('batch')
                                        ->orWhere('batch', $student->batch);
                                })
                                    ->whereNull('semester');
                            });
                        }
                    });
            })
            ->with([
                'schedule' => function ($q) use ($student, $isFirstYearStudent) {
                    $q->whereDate('event_date', '>', Carbon::now()->toDateString())
                        ->where(function ($subQ) use ($student, $isFirstYearStudent) {

                            // Normal events
                            $subQ->where(function ($normalQ) use ($student) {
                                $normalQ->where('programme_id', $student->programme_id)
                                    ->where('section', $student->section)
                                    ->where('batch', $student->batch)
                                    ->where('semester', $student->semester);
                            });

                            // First year common events
                            if ($isFirstYearStudent) {
                                $subQ->orWhere(function ($firstYearQ) use ($student) {
                                    $firstYearQ->whereNull('programme_id')
                                        ->whereNull('section')
                                    ->where(function ($batchQ) use ($student) {
                                        $batchQ->whereNull('batch')
                                            ->orWhere('batch', $student->batch);
                                    })
                                        ->whereNull('semester');
                                });
                            }
                        })
                        ->orderBy('event_date', 'asc');
                },
                'schedule.registrations',
                'event'
            ])
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

        $earnedCredits = StudentEventRegistration::with(['event', 'get_event_schedule:id,credit_points,programme_id,section,batch,semester'])
            ->where('student_id', $student->id)
            ->whereNotNull('grade')
            ->whereRaw('LOWER(grade) != ?', ['d'])
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })
            ->whereHas('get_event_schedule', function ($q) use ($student, $isFirstYearStudent) {
                $q->where(function ($subQ) use ($student, $isFirstYearStudent) {

                    // Normal events
                    $subQ->where(function ($normalQ) use ($student) {
                        $normalQ->where('programme_id', $student->programme_id)
                            ->where('batch', $student->batch)
                            ->where('semester', $student->semester);
                    });

                    // First year common events
                    if ($isFirstYearStudent) {
                        $subQ->orWhere(function ($firstYearQ) use ($student) {
                            $firstYearQ->whereNull('programme_id')
                                ->whereNull('section')
                                ->whereNull('semester')
                                ->where(function ($batchQ) use ($student) {
                                    $batchQ->whereNull('batch')
                                        ->orWhere('batch', $student->batch);
                                });
                        });
                    }
                });
            })
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

    private function applyStudentScheduleFilter($q, $student, $isFirstYearStudent)
    {
        $q->where(function ($subQ) use ($student, $isFirstYearStudent) {
            $subQ->where(function ($normalQ) use ($student) {
                $normalQ->where('programme_id', $student->programme_id)
                    ->where('section', $student->section)
                    ->where('batch', $student->batch)
                    ->where('semester', $student->semester);
            });
            if ($isFirstYearStudent) {
                $subQ->orWhere(function ($firstYearQ) use ($student) {
                    $firstYearQ->whereNull('programme_id')
                        ->whereNull('section')
                        ->whereNull('semester')
                        ->where(function ($batchQ) use ($student) {
                            $batchQ->whereNull('batch')
                                ->orWhere('batch', $student->batch);
                        });
                });
            }
        });
    }
}
