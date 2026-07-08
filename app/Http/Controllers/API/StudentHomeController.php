<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CreditPoint;
use App\Models\Event;
use App\Models\StudentEventRegistration;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StudentHomeController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $student = auth('student-api')->user();
        $isFirstYearStudent = in_array((int)$student->semester, [1, 2]);
        $totalEvents = Event::where([
            'publish' => 1,
            'is_active' => 'y'
        ])->count();
        $registeredCount = StudentEventRegistration::where('student_id', $student->id)
            ->count();
        $completedCount = StudentEventRegistration::where('student_id', $student->id)
            ->where('status', 3)
            ->count();
        $certificateEarned = StudentEventRegistration::where('student_id', $student->id)
            ->where('status', 2)
            ->whereNotNull('grade')
            ->count();
        $today = Carbon::today()->toDateString();
        $configCredit = CreditPoint::where('semester', $student->semester)->first();

        $upcomingEvents =  Event::whereHas('get_dep_events', function ($q) use ($student) {
            $q->where('programme_id', $student->programme_id)
                ->where('section', $student->section)
                ->where('batch', $student->batch)
                ->where('semester', $student->semester)
                ->where('event_date', '>=', Carbon::now()->toDateString()); // Only future dates
        })
            ->with(['get_dep_events' => function ($q) use ($student) {
                $q->where('programme_id', $student->programme_id)
                    ->where('section', $student->section)
                    ->where('batch', $student->batch)
                    ->where('semester', $student->semester)
                    ->where('event_date', '>=', Carbon::now()->toDateString())
                    ->orderBy('event_date', 'asc');
            }, 'get_dep_events.registrations'])
            ->where([
                'publish' => 1,
                'is_active' => 'y'
            ])
            ->get()
            ->flatMap(function ($event) use ($student, $now) {
                return $event->get_dep_events->map(function ($schedule) use ($event, $student, $now) {
                    $registeredCount = $this->registeredSeatsForSchedule($schedule, $student);
                    $availableSeats = max(0, $schedule->seat_count - $registeredCount);
                    [$startTime, $endTime] = $this->eventTimesForSchedule($event, $schedule);
                    $deadline = Carbon::parse($event->end_registration);
                    $lastRegistration = $event->registrations
                        ->where('student_id', $student->id)
                        ->where('event_schedule_id', $schedule->id)
                        ->sortByDesc('registered_at')
                        ->first();

                    $cooldownActive = false;
                    $permanentBlock = false;
                    $nextAllowedDate = null;

                    if ($lastRegistration) {
                        if (empty($event->duration_months) || $event->duration_months == 0) {
                            $permanentBlock = true;
                        }

                        if (!$permanentBlock && $event->duration_months) {
                            $nextAllowedDate = Carbon::parse($lastRegistration->registered_at)
                                ->addMonths($event->duration_months);

                            if ($now->lt($nextAllowedDate)) {
                                $cooldownActive = true;
                            }
                        }
                    }

                    $paidEventConflict = StudentEventRegistration::where('student_id', $student->id)
                        ->whereHas('event', function ($q) {
                            $q->where('event_type', 'paid');
                        })
                        ->whereHas('get_event_schedule', function ($q) use ($schedule) {
                            $q->whereDate('event_date', $schedule->event_date);
                        })
                        ->exists();

                    $canRegister =
                        !$permanentBlock &&
                        !$cooldownActive &&
                        $availableSeats > 0 &&
                        !$deadline->endOfDay()->isPast() &&
                        !$paidEventConflict;

                    return [
                        'event_id' => $event->id,
                        'schedule_id' => $schedule->id,
                        'event_image' => $event->banner_image ? asset('storage/' . $event->banner_image) : null,
                        'event_name' => $event->title,
                        'event_start_time' => $startTime ? \Carbon\Carbon::parse($startTime)->format('h:iA') : '-',
                        'event_end_time' =>  $endTime ? \Carbon\Carbon::parse($endTime)->format('h:iA') : '-',
                        'event_location' => $event->location,
                        'event_date' => Carbon::parse($schedule->event_date)->format('F d, Y'),

                        'total_seats' => $schedule->seat_count,
                        'registered_seats' => $registeredCount,
                        'available_seats' => $availableSeats,

                        'event_premium' => $event->event_type === 'paid' ? 'paid' : 'free',
                        'event_register' => $canRegister,

                        'registration_status' => match (true) {
                            $permanentBlock => 'already_registered',
                            $cooldownActive => 'cooldown_active',
                            $availableSeats <= 0 => 'seats_full',
                            $deadline->endOfDay()->isPast() => 'registration_closed',
                            $paidEventConflict => 'paid_event_conflict',
                            default => 'open',
                        },

                        'next_allowed_date' => $nextAllowedDate
                            ? $nextAllowedDate->format('Y-m-d')
                            : null,

                        'student_name' => $student->name,
                        'student_id' => $student->id,
                        'student_email' => $student->email,
                        'student_number' => $student->mobile_number,
                    ];
                });
            })
            ->filter()
            ->values();
        $ongoingEvents = Event::whereHas('get_dep_events', function ($q) use ($student) {
            $q->where('programme_id', $student->programme_id)
                ->where('section', $student->section)
                ->where('batch', $student->batch)
                ->where('semester', $student->semester)
                ->where('event_date', '=', Carbon::now()->toDateString()); // Only future dates
        })
            ->with(['get_dep_events' => function ($q) use ($student) {
                $q->where('programme_id', $student->programme_id)
                    ->where('section', $student->section)
                    ->where('batch', $student->batch)
                    ->where('semester', $student->semester)
                    ->where('event_date', '=', Carbon::now()->toDateString())
                    ->orderBy('event_date', 'asc');
            }, 'get_dep_events.registrations'])
            ->where([
                'publish' => 1,
                'is_active' => 'y'
            ])
            ->get()
            ->flatMap(function ($event) use ($student, $now) {
                return $event->get_dep_events->map(function ($schedule) use ($event, $student, $now) {
                    $registeredCount = $this->registeredSeatsForSchedule($schedule, $student);
                    $availableSeats = max(0, $schedule->seat_count - $registeredCount);
                    [$startTime, $endTime] = $this->eventTimesForSchedule($event, $schedule);
                    $deadline = Carbon::parse($event->end_registration);
                    $lastRegistration = $event->registrations
                        ->where('student_id', $student->id)
                        ->where('event_schedule_id', $schedule->id)
                        ->sortByDesc('registered_at')
                        ->first();

                    $cooldownActive = false;
                    $permanentBlock = false;
                    $nextAllowedDate = null;

                    if ($lastRegistration) {
                        if (empty($event->duration_months) || $event->duration_months == 0) {
                            $permanentBlock = true;
                        }

                        if (!$permanentBlock && $event->duration_months) {
                            $nextAllowedDate = Carbon::parse($lastRegistration->registered_at)
                                ->addMonths($event->duration_months);

                            if ($now->lt($nextAllowedDate)) {
                                $cooldownActive = true;
                            }
                        }
                    }

                    $paidEventConflict = StudentEventRegistration::where('student_id', $student->id)
                        ->whereHas('event', function ($q) {
                            $q->where('event_type', 'paid');
                        })
                        ->whereHas('get_event_schedule', function ($q) use ($schedule) {
                            $q->whereDate('event_date', $schedule->event_date);
                        })
                        ->exists();

                    $canRegister =
                        !$permanentBlock &&
                        !$cooldownActive &&
                        $availableSeats > 0 &&
                        !$deadline->endOfDay()->isPast() &&
                        !$paidEventConflict;

                    return [
                        'event_id' => $event->id,
                        'schedule_id' => $schedule->id,
                        'event_image' => $event->banner_image ? asset('storage/' . $event->banner_image) : null,
                        'event_name' => $event->title,
                        'event_start_time' => $startTime ? \Carbon\Carbon::parse($startTime)->format('h:iA') : '-',
                        'event_end_time' =>  $endTime ? \Carbon\Carbon::parse($endTime)->format('h:iA') : '-',
                        'event_location' => $event->location,
                        'event_date' => Carbon::parse($schedule->event_date)->format('F d, Y'),

                        'total_seats' => $schedule->seat_count,
                        'registered_seats' => $registeredCount,
                        'available_seats' => $availableSeats,

                        'event_premium' => $event->event_type === 'paid' ? 'paid' : 'free',
                        'event_register' => $canRegister,

                        'registration_status' => match (true) {
                            $permanentBlock => 'already_registered',
                            $cooldownActive => 'cooldown_active',
                            $availableSeats <= 0 => 'seats_full',
                            $deadline->endOfDay()->isPast() => 'registration_closed',
                            $paidEventConflict => 'paid_event_conflict',
                            default => 'open',
                        },

                        'next_allowed_date' => $nextAllowedDate
                            ? $nextAllowedDate->format('Y-m-d')
                            : null,

                        'student_name' => $student->name,
                        'student_id' => $student->id,
                        'student_email' => $student->email,
                        'student_number' => $student->mobile_number,
                    ];
                });
            })
            ->filter()
            ->values();
        $registeredEvents = StudentEventRegistration::with([
            'event',
            'get_event_schedule'
        ])
            ->where('student_id', $student->id)
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })
            ->get()
            ->map(function ($registration) use ($student) {
                $event = $registration->event;
                $schedule = $registration->get_event_schedule;

                if (!$event || !$schedule) {
                    return null;
                }

                $registeredSeats = $this->registeredSeatsForSchedule($schedule, $student);
                $availableSeats = max(0, $schedule->seat_count - $registeredSeats);
                [$startTime, $endTime] = $this->eventTimesForSchedule($event, $schedule);

                return [
                    'event_id' => $event->id,
                    'schedule_id' => $schedule->id,
                    'registration_id' => $registration->id,
                    'event_image' => $event->banner_image
                        ? asset('storage/' . $event->banner_image)
                        : null,
                    'event_name' => $event->title ?? '',
                    'event_start_time' => $startTime ? Carbon::parse($startTime)->format('h:i A') : null,
                    'event_end_time' => $endTime ? Carbon::parse($endTime)->format('h:i A') : null,
                    'event_location' => $event->location,
                    'event_date' => Carbon::parse($schedule->event_date)->format('F d, Y'),

                    'total_seats' => $schedule->seat_count,
                    'registered_seats' => $registeredSeats,
                    'available_seats' => $availableSeats,

                    'event_premium' => $event->event_type == 'paid' ? 'paid' : 'free',
                    'event_register' => false,
                    'registration_status' => $registration->status,
                    'grade' => $registration->grade,
                    'registered_at' => $registration->registered_at
                        ? Carbon::parse($registration->registered_at)->format('Y-m-d H:i:s')
                        : null,

                    'student_name' => $student->name,
                    'student_id' => $student->id,
                    'student_email' => $student->email,
                    'student_number' => $student->mobile_number,
                ];
            })
            ->filter()
            ->values();
        $earnedCredits = StudentEventRegistration::with('get_event_schedule')
            ->where('student_id', $student->id)
            ->whereNotNull('grade')
            ->whereRaw('LOWER(grade) != ?', ['d'])
            ->get()
            ->sum(fn($item) => $item->get_event_schedule->credit_points ?? 0);
        $earned = min($earnedCredits, 4);
        $pending = max(0, ($configCredit?->credit_points ?? 0) - $earned);
        return response()->json([
            'status' => 200,
            'msg' => 'Home data fetched Successful',
            'user_image' => $student->profile_pic
                ? asset('storage/' . $student->profile_pic)
                : null,
            'user_name' => $student->name,
            'notification' => true,
            'total_events' => $totalEvents,
            'register_events' => $registeredCount,
            'completed' => $completedCount,
            'certification_earned' => $certificateEarned,
            'credit' => $configCredit?->credit_points ?? 0,
            'earned' => $earned,
            'pending' => $pending,
            'data' => [
                'upcoming_event' => $upcomingEvents,
                'ongoing_event' => $ongoingEvents,
                'registered_event' => $registeredEvents,
            ]
        ]);
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

    private function registeredSeatsForSchedule($schedule, $student)
    {
        $query = StudentEventRegistration::where('event_schedule_id', $schedule->id);

        if ($this->isCommonFirstYearSchedule($schedule, $student)) {
            $query->whereHas('student', function ($studentQuery) use ($student) {
                $studentQuery->whereIn('semester', [1, 2]);

                if (!empty($student->batch)) {
                    $studentQuery->where('batch', $student->batch);
                }
            });

            return $query->count();
        }

        return $query->whereHas('student', function ($studentQuery) use ($student) {
            $studentQuery
                ->where('programme_id', $student->programme_id)
                ->where('section', $student->section)
                ->where('batch', $student->batch)
                ->where('semester', $student->semester);
        })->count();
    }

    private function isCommonFirstYearSchedule($schedule, $student)
    {
        return is_null($schedule->programme_id)
            && is_null($schedule->section)
            && is_null($schedule->semester)
            && (is_null($schedule->batch) || $schedule->batch == $student->batch);
    }

    private function eventTimesForSchedule($event, $schedule)
    {

        if ($schedule->is_reserve_date == 'y') {
            return [$event->reserve_start_time, $event->reserve_end_time];
        }

        return [$event->start_time, $event->end_time];
    }
}
