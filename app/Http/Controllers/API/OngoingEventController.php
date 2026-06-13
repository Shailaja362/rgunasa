<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\StudentEventRegistration;
use App\Models\StudentUploadProof;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OngoingEventController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        $student = auth('student-api')->user();

        if (!$student) {
            return response()->json([
                'status'  => 401,
                'message' => 'Unauthorized',
            ], 401);
        }

        $isFirstYearStudent = in_array((int) $student->semester, [1, 2]);

        // Active Registration Count
        $activeCount = StudentEventRegistration::where('student_id', $student->id)
            ->where('status', 1)
            ->whereHas('event', fn($q) => $q->where('publish', 1)->where('is_active', 'y'))
            ->count();

        // Attended Count
        $attendedCount = StudentEventRegistration::where('student_id', $student->id)
            ->where('status', 3)
            ->whereHas('get_event_attendance', fn($q) => $q->whereNotNull('entry_time')->whereNotNull('exit_time'))
            ->whereHas('event', fn($q) => $q->where('publish', 1)->where('is_active', 'y'))
            ->count();

        // Pending Uploads
        $totalRegistered = StudentEventRegistration::where('student_id', $student->id)
            ->whereHas('event', fn($q) => $q->where('publish', 1)->where('is_active', 'y'))
            ->count();

        $myUploads = StudentUploadProof::select('student_id', 'event_id')
            ->whereHas('event', fn($q) => $q->where('publish', 1)->where('is_active', 'y'))
            ->where('student_id', $student->id)
            ->groupBy('student_id', 'event_id')
            ->get();

        $pendingUploads = $totalRegistered - count($myUploads);

        // Student Registrations (for conflict check)
        $studentRegistrations = StudentEventRegistration::where('student_id', $student->id)
            ->with('event')
            ->whereHas('event', fn($q) => $q->where('publish', 1)->where('is_active', 'y'))
            ->get();

        // Ongoing Events Only
        $ongoingEvents = Event::whereHas('get_dep_events', function ($q) use ($student, $isFirstYearStudent, $now) {
            $q->whereDate('event_date', $now->toDateString());
            $this->applyStudentScheduleFilter($q, $student, $isFirstYearStudent);
        })
            ->with([
                'get_dep_events' => function ($q) use ($student, $isFirstYearStudent, $now) {
                    $q->whereDate('event_date', $now->toDateString());
                    $this->applyStudentScheduleFilter($q, $student, $isFirstYearStudent);
                },
                'get_dep_events.registrations'
            ])
            ->where('publish', 1)
            ->where('is_active', 'y')
            ->get();

        // Build ongoing event data
        $eventData = [];

        foreach ($ongoingEvents as $event) {
            foreach ($event->get_dep_events as $dept) {

                $eventDate = Carbon::parse($dept->event_date)->toDateString();

                // Check common first year event
                $isCommonFirstYearEvent =
                    is_null($dept->programme_id) &&
                    is_null($dept->section) &&
                    is_null($dept->semester) &&
                    (is_null($dept->batch) || $dept->batch == $student->batch);

                // Seat count query
                $registeredCountQuery = StudentEventRegistration::where('event_schedule_id', $dept->id);

                if ($isCommonFirstYearEvent) {
                    $registeredCountQuery->whereHas('student', function ($q) use ($student) {
                        $q->whereIn('semester', [1, 2]);
                        if (!empty($student->batch)) {
                            $q->where('batch', $student->batch);
                        }
                    });
                } else {
                    $registeredCountQuery->whereHas('student', function ($q) use ($student) {
                        $q->where('programme_id', $student->programme_id)
                            ->where('section', $student->section)
                            ->where('batch', $student->batch)
                            ->where('semester', $student->semester);
                    });
                }

                $registeredCount = $registeredCountQuery->count();
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
                    ->where('event_schedule_id', $dept->id)
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

                $paidEventConflict = $studentRegistrations
                    ->where('event.event_type', 'paid')
                    ->where('event.event_date', $eventDate)
                    ->first();

                $canRegister =
                    !$permanentBlock &&
                    !$cooldownActive &&
                    $availableSeats > 0 &&
                    !$deadline->endOfDay()->isPast() &&
                    !$paidEventConflict;

                $eventData[] = [
                    'event_id'          => $event->id,
                    'event_image'       => $event->banner_image ? asset('storage/' . $event->banner_image) : null,
                    'event_name'        => $event->title,
                    'event_description' => $event->description ?? null,
                    'event_start_time'  => $start_time ? Carbon::parse($start_time)->format('g:i A') : null,
                    'event_end_time'    => $end_time ? Carbon::parse($end_time)->format('g:i A') : null,
                    'event_seats'       => $availableSeats,
                    'event_location'    => $event->location,
                    'event_date'        => Carbon::parse($dept->event_date)->format('F j, Y'),
                    'event_premium'     => $event->event_type === 'paid' ? 'paid' : 'free',
                    'event_register'    => $canRegister,
                    'student_name'      => $student->name,
                    'student_id'        => $student->id,
                    'student_email'     => $student->email,
                    'student_number'    => $student->phone ?? null,
                ];
            }
        }

        return response()->json([
            'status'                    => 200,
            'mgs'                       => 'Ongoing Successful',
            'active_registration_count' => $activeCount,
            'attended_events'           => $attendedCount,
            'pending_events'            => $pendingUploads,
            'data'                      => $eventData,
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
}
