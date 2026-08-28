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
        $today = Carbon::today()->toDateString();
        if (!$student) {
            return response()->json([
                'status' => 401,
                'mgs'    => 'Unauthenticated',
            ], 401);
        }

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

        $studentRegistrations = StudentEventRegistration::where('student_id', $student->id)
            ->with('event')
            ->whereHas('event', fn($q) => $q->where('publish', 1)->where('is_active', 'y'))
            ->get();

        $paidRegisteredDates = \App\Models\StudentEventRegistration::where('student_id', $student->id)
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

            $q->openToProgramme($student->programme_id)
                ->openToSection($student->section)
                ->openToBatch($student->batch)
                ->openToSemester($student->semester)
                ->whereDate('event_date', '>=', Carbon::today());
        })
            ->with([
                'get_dep_events' => function ($q) use ($student) {
                    $q->openToProgramme($student->programme_id)
                        ->openToSection($student->section)
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
                    'event_seats'       => $availableSeats,
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

        return response()->json([
            'status'                    => 200,
            'mgs'                       => 'Upcoming Successful',
            'active_registration_count' => $activeRegistrationCount,
            'attended_events'           => $attendedEvents,
            'pending_events'            => $pendingUploads,
            'data'                      => $upcomingEventData,
        ]);
    }

    public static function registeredSeatsForSchedule($schedule, $student)
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
