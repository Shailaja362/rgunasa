<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\StudentEventRegistration;
use App\Models\StudentUploadProof;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UpcomingEventController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        $student = auth('student-api')->user();

        if (!$student) {
            return response()->json([
                'status' => 401,
                'mgs'    => 'Unauthenticated',
            ], 401);
        }

        $isFirstYearStudent = in_array((int) $student->semester, [1, 2]);

        $myUploads = StudentUploadProof::select('student_id', 'event_id')
            ->whereHas('event', fn($q) => $q->where('publish', 1)->where('is_active', 'y'))
            ->where('student_id', $student->id)
            ->groupBy('student_id', 'event_id')
            ->get();

        $activeRegistrationCount = StudentEventRegistration::where('student_id', $student->id)
            ->where('status', 1)
            ->whereHas('event', fn($q) => $q->where('publish', 1)->where('is_active', 'y'))
            ->count();

        $registeredCount = StudentEventRegistration::where('student_id', $student->id)
            ->whereHas('event', fn($q) => $q->where('publish', 1)->where('is_active', 'y'))
            ->count();

        $attendedEvents = StudentEventRegistration::where('student_id', $student->id)
            ->where('status', 3)
            ->whereHas('get_event_attendance', fn($q) => $q->whereNotNull('entry_time')->whereNotNull('exit_time'))
            ->whereHas('event', fn($q) => $q->where('publish', 1)->where('is_active', 'y'))
            ->count();

        $pendingUploads = max(0, $registeredCount - $myUploads->count());

        $studentRegistrations = StudentEventRegistration::where('student_id', $student->id)
            ->with(['event', 'get_event_schedule'])
            ->whereHas('event', fn($q) => $q->where('publish', 1)->where('is_active', 'y'))
            ->get();

        $today = Carbon::today()->toDateString();

        $upcomingEvents = Event::whereHas('get_dep_events', function ($q) use ($student, $isFirstYearStudent, $today) {
            $q->whereDate('event_date', '>=', $today);
            $this->applyStudentScheduleFilter($q, $student, $isFirstYearStudent);
        })
            ->with([
                'registrations',
                'get_dep_events' => function ($q) use ($student, $isFirstYearStudent, $today) {
                    $q->whereDate('event_date', '>=', $today);
                    $this->applyStudentScheduleFilter($q, $student, $isFirstYearStudent);
                    $q->orderBy('event_date', 'asc');
                },
                'get_dep_events.registrations',
            ])
            ->where('publish', 1)
            ->where('is_active', 'y')
            ->get();

        // ✅ Fix 3: Debug — check if events are being fetched at all
        if ($upcomingEvents->isEmpty()) {
            return response()->json([
                'status'                    => 200,
                'mgs'                       => 'Upcoming Successful',
                'active_registration_count' => $activeRegistrationCount,
                'attended_events'           => $attendedEvents,
                'pending_events'            => $pendingUploads,
                'data'                      => [],
                'debug'                     => 'No upcoming events found for this student filter',
            ]);
        }

        return response()->json([
            'status'                    => 200,
            'mgs'                       => 'Upcoming Successful',
            'active_registration_count' => $activeRegistrationCount,
            'attended_events'           => $attendedEvents,
            'pending_events'            => $pendingUploads,
            'data'                      => $this->formatEventCards($upcomingEvents, $student, $studentRegistrations, $now),
        ]);
    }

    private function formatEventCards($events, $student, $studentRegistrations, $now)
    {
        return $events
            ->flatMap(function ($event) use ($student, $studentRegistrations, $now) {
                return $event->get_dep_events->map(function ($schedule) use ($event, $student, $studentRegistrations, $now) {

                    $registeredCount = $this->registeredSeatsForSchedule($schedule, $student);
                    $availableSeats  = max(0, $schedule->seat_count - $registeredCount);
                    [$startTime, $endTime] = $this->eventTimesForSchedule($event, $schedule);
                    $deadline = Carbon::parse($event->end_registration);

                    // ✅ Fix 4: use $event->registrations (eager loaded on event)
                    $lastRegistration = $event->registrations
                        ->where('student_id', $student->id)
                        ->where('event_schedule_id', $schedule->id)
                        ->sortByDesc('registered_at')
                        ->first();

                    $cooldownActive  = false;
                    $permanentBlock  = false;
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

                    $eventDate = Carbon::parse($schedule->event_date)->toDateString();

                    // ✅ Fix 5: conflict check using get_event_schedule relation
                    $paidEventConflict = $studentRegistrations
                        ->where('event.event_type', 'paid')
                        ->filter(function ($registration) use ($eventDate) {
                            if (!$registration->get_event_schedule) return false;
                            return Carbon::parse($registration->get_event_schedule->event_date)
                                ->toDateString() === $eventDate;
                        })
                        ->first();

                    $canRegister =
                        !$permanentBlock &&
                        !$cooldownActive &&
                        $availableSeats > 0 &&
                        !$deadline->endOfDay()->isPast() &&
                        !$paidEventConflict;

                    return [
                        'event_id'          => $event->id,
                        'event_image'       => $event->banner_image
                            ? asset('storage/' . $event->banner_image)
                            : null,
                        'event_name'        => $event->title,
                        'event_description' => $event->description,
                        'event_start_time'  => $startTime ? Carbon::parse($startTime)->format('h:i A') : null,
                        'event_end_time'    => $endTime ? Carbon::parse($endTime)->format('h:i A') : null,
                        'event_seats'       => $availableSeats,
                        'event_location'    => $event->location,
                        'event_date'        => Carbon::parse($schedule->event_date)->format('F d, Y'),
                        'event_premium'     => $event->event_type === 'paid' ? 'paid' : 'free',
                        'event_register'    => $canRegister,
                        'student_name'      => $student->name,
                        'student_id'        => $student->id,
                        'student_email'     => $student->email,
                        'student_number'    => $student->mobile_number,
                    ];
                });
            })
            ->filter()
            ->values();
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
