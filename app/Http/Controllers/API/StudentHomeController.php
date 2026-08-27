<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CreditPoint;
use App\Models\Event;
use App\Models\Notification;
use App\Models\StudentEventRegistration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            ->whereNotNull('grade')
            ->count();
        $today = Carbon::today()->toDateString();
        $configCredit = CreditPoint::where('semester', $student->semester)->first();
        $studentRegistrations = StudentEventRegistration::where('student_id', $student->id)
            ->with('event')
            ->whereHas('event', fn($q) => $q->where('publish', 1)->where('is_active', 'y'))
            ->get();
        $paidRegisteredDates = StudentEventRegistration::where('student_id', $student->id)
            ->whereHas('event', function ($q) {
                $q->where('event_type', 'paid');
            })
            ->join('event_schedules', 'student_event_registrations.event_schedule_id', '=', 'event_schedules.id')
            ->pluck('event_schedules.event_date')
            ->filter()
            ->map(fn($date) => \Carbon\Carbon::parse($date)->toDateString())
            ->unique()
            ->values()
            ->toArray();
        $upcomingEvents = Event::whereHas('get_dep_events', function ($q) use ($student) {

            $q->where('programme_id', $student->programme_id)
                ->where('section', $student->section)
                ->openToBatch($student->batch)
                ->openToSemester($student->semester)
                ->whereDate('event_date', '>=', Carbon::today());
        })
            ->with([
                'get_dep_events' => function ($q) use ($student) {
                    $q->where('programme_id', $student->programme_id)
                        ->where('section', $student->section)
                        ->openToBatch($student->batch)
                        ->openToSemester($student->semester)
                        ->whereDate('event_date', '>=', Carbon::today())
                        ->orderBy('event_date', 'asc');
                },
                'get_dep_events.registrations'
            ])
            ->where([
                'publish'   => 1,
                'is_active' => 'y',
            ])
            ->get();

        $upcomingEventData = [];

        foreach ($upcomingEvents as $event) {

            foreach ($event->get_dep_events as $dept) {

                $eventDate = Carbon::parse($dept->event_date)->toDateString();

                // seat_count is one shared pool for the whole schedule row (across
                // every batch/semester it's open to), so count every registration
                // on this row, not just ones matching the viewing student's cohort.
                $registeredCount = StudentEventRegistration::where('event_schedule_id', $dept->id)->count();
                $availableSeats = max(0, $dept->seat_count - $registeredCount);

                if ($dept->is_reserve_date == 'y') {

                    $start_time = $event->reserve_start_time;
                    $end_time   = $event->reserve_end_time;
                } else {

                    $start_time = $event->start_time;
                    $end_time   = $event->end_time;
                }

                $deadline = Carbon::parse($event->end_registration);

                $lastRegistration = $event->registrations
                    ->where('student_id', $student->id)
                    ->where('event_id', $dept->event_id)
                    ->sortByDesc('registered_at')
                    ->first();

                $cooldownActive = false;
                $permanentBlock = false;
                $nextAllowedDate = null;

                if ($lastRegistration) {

                    if (empty($event->duration_days) || $event->duration_days == 0) {

                        $permanentBlock = true;
                    }

                    if (!$permanentBlock && $event->duration_days) {

                        $nextAllowedDate = Carbon::parse($lastRegistration->registered_at)
                            ->addDays($event->duration_days);

                        if ($now->lt($nextAllowedDate)) {
                            $cooldownActive = true;
                        }
                    }
                }
                $eventDate = \Carbon\Carbon::parse($dept->event_date)->toDateString();
                $paidEventConflict = in_array($eventDate, $paidRegisteredDates);
                $canRegister =
                    !$permanentBlock &&
                    !$cooldownActive &&
                    $availableSeats > 0 &&
                    !$deadline->endOfDay()->isPast() &&
                    !$paidEventConflict;
                $text = '';
                $eventRegister = true;
                if ($permanentBlock) {
                    $eventRegister = false;
                    $text = 'You have already registered for this event and cannot register again.';
                } elseif ($cooldownActive) {
                    $eventRegister = false;
                    $text = 'You can register again after ' . $nextAllowedDate->format('F j, Y');
                } elseif ($availableSeats <= 0) {
                    $text = 'No available seats for this event.';
                } elseif ($deadline->endOfDay()->isPast()) {
                    $text = 'Registration deadline has passed.';
                } elseif ($paidEventConflict) {
                    $text = 'You have already registered for a paid event on this date.';
                }

                $upcomingEventData[] = [
                    'event_id'          => $event->id,
                    'schedule_id'       => $dept->id,
                    'event_image'       => $event->banner_image ? asset('storage/' . $event->banner_image) : null,
                    'event_name'        => $event->title,
                    'event_description' => $event->description,
                    'event_start_time'  => $start_time ? Carbon::parse($start_time)->format('g:i A') : null,
                    'event_end_time'    => $end_time ? Carbon::parse($end_time)->format('g:i A') : null,
                    'available_seats'       => $availableSeats,
                    'event_location'    => $event->location,
                    'event_date'        => Carbon::parse($dept->event_date)->format('F j, Y'),
                    'event_premium'     => $event->event_type == 'paid' ? 'paid' : 'free',
                    'event_register'    => $eventRegister,
                    'student_name'      => $student->name,
                    'student_id'        => $student->id,
                    'student_email'     => $student->email,
                    'student_number'    => $student->mobile_number,
                    'message'           => $text,
                    'end_registration'  => $event->end_registration,
                    'event_amount'      => $event->price
                ];
            }
        }


        $ongoingEvents = Event::whereHas('get_dep_events', function ($q) use ($student) {
            $q->where('programme_id', $student->programme_id)
                ->where('section', $student->section)
                ->openToBatch($student->batch)
                ->openToSemester($student->semester)
                ->where('event_date', '=', Carbon::now()->toDateString()); // Only future dates
        })
            ->with(['get_dep_events' => function ($q) use ($student) {
                $q->where('programme_id', $student->programme_id)
                    ->where('section', $student->section)
                    ->openToBatch($student->batch)
                    ->openToSemester($student->semester)
                    ->where('event_date', '=', Carbon::now()->toDateString())
                    ->orderBy('event_date', 'asc');
            }, 'get_dep_events.registrations'])
            ->where([
                'publish' => 1,
                'is_active' => 'y'
            ])
            ->get();

        // Build ongoing event data
        $eventData = [];

        foreach ($ongoingEvents as $event) {
            foreach ($event->get_dep_events as $dept) {

                $eventDate = Carbon::parse($dept->event_date)->toDateString();

                // seat_count is one shared pool for the whole schedule row (across
                // every batch/semester it's open to), so count every registration
                // on this row, not just ones matching the viewing student's cohort.
                $registeredCount = StudentEventRegistration::where('event_schedule_id', $dept->id)->count();
                $availableSeats  = max(0, $dept->seat_count - $registeredCount);

                // Time
                if ($dept->is_reserve_date == 'y') {
                    $start_time = $event->reserve_start_time;
                    $end_time   = $event->reserve_end_time;
                } else {
                    $start_time = $event->start_time;
                    $end_time   = $event->end_time;
                }

                // Registration eligibility
                $deadline         = Carbon::parse($event->end_registration);
                $lastRegistration = $event->registrations
                    ->where('student_id', $student->id)
                    ->where('event_id', $dept->event_id)
                    ->sortByDesc('registered_at')
                    ->first();

                $cooldownActive  = false;
                $permanentBlock  = false;
                $nextAllowedDate = null;

                if ($lastRegistration) {
                    if (empty($event->duration_days) || $event->duration_days == 0) {
                        $permanentBlock = true;
                    }
                    if (!$permanentBlock && $event->duration_days) {
                        $nextAllowedDate = Carbon::parse($lastRegistration->registered_at)
                            ->addDays($event->duration_days);
                        if ($now->lt($nextAllowedDate)) {
                            $cooldownActive = true;
                        }
                    }
                }
                $eventDate = \Carbon\Carbon::parse($dept->event_date)->toDateString();
                $paidEventConflict = in_array($eventDate, $paidRegisteredDates);
                $canRegister =
                    !$permanentBlock &&
                    !$cooldownActive &&
                    $availableSeats > 0 &&
                    !$deadline->endOfDay()->isPast() &&
                    !$paidEventConflict;
                $text = '';
                $eventRegister = true;
                if ($permanentBlock) {
                    $eventRegister = false;
                    $text = 'You have already registered for this event and cannot register again.';
                } elseif ($cooldownActive) {
                    $eventRegister = false;
                    $text = 'You can register again after ' . $nextAllowedDate->format('F j, Y');
                } elseif ($availableSeats <= 0) {
                    $text = 'No available seats for this event.';
                } elseif ($deadline->endOfDay()->isPast()) {
                    $text = 'Registration deadline has passed.';
                } elseif ($paidEventConflict) {
                    $text = 'You have already registered for a paid event on this date.';
                }
                $eventData[] = [
                    'event_id'          => $event->id,
                    'schedule_id'          => $dept->id,
                    'event_image'       => $event->banner_image ? asset('storage/' . $event->banner_image) : null,
                    'event_name'        => $event->title,
                    'event_description' => $event->description ?? null,
                    'event_start_time'  => $start_time ? Carbon::parse($start_time)->format('g:i A') : null,
                    'event_end_time'    => $end_time ? Carbon::parse($end_time)->format('g:i A') : null,
                    'available_seats'       => $availableSeats,
                    'event_location'    => $event->location,
                    'event_date'        => Carbon::parse($dept->event_date)->format('F j, Y'),
                    'event_premium'     => $event->event_type === 'paid' ? 'paid' : 'free',
                    'event_register'    => $eventRegister,
                    'student_name'      => $student->name,
                    'student_id'        => $student->id,
                    'student_email'     => $student->email,
                    'student_number'    => $student->mobile_number ?? null,
                    'message'           => $text,
                    'end_registration'  => $event->end_registration,
                    'event_amount'      => $event->price
                ];
            }
        }
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
        $readStatus = Notification::where([
            'student_id' => $student->id,
            'role' => 2,
            'status' => 0
        ])->exists();
        $registered_count = StudentEventRegistration::with('event')
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })->where('student_id', $student->id)->get();
        return response()->json([
            'status' => 200,
            'msg' => 'Home data fetched Successful',
            'user_image' => $student->profile_pic
                ? asset('storage/' . $student->profile_pic)
                : null,
            'user_name' => $student->name,
            'notification' => $readStatus,
            'total_events' => $totalEvents,
            'register_events' => count($registered_count),
            'completed' => $completedCount,
            'certification_earned' => $certificateEarned,
            'credit' => $configCredit?->credit_points ?? 0,
            'earned' => $earned,
            'pending' => $pending,
            'data' => [
                'upcoming_event' =>  $upcomingEventData,
                'ongoing_event' => $eventData,
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
                    ->openToBatch($student->batch)
                    ->openToSemester($student->semester);
            });
            if ($isFirstYearStudent) {
                $subQ->orWhere(function ($firstYearQ) use ($student) {
                    $firstYearQ->whereNull('programme_id')
                        ->whereNull('section')
                        ->whereNull('semester')
                        ->openToBatch($student->batch);
                });
            }
        });
    }

    private function registeredSeatsForSchedule($schedule, $student)
    {
        // seat_count is a shared pool for the whole schedule row, so count every
        // registration on it, regardless of the viewing student's own cohort.
        return StudentEventRegistration::where('event_schedule_id', $schedule->id)->count();
    }

    private function eventTimesForSchedule($event, $schedule)
    {

        if ($schedule->is_reserve_date == 'y') {
            return [$event->reserve_start_time, $event->reserve_end_time];
        }

        return [$event->start_time, $event->end_time];
    }
}
