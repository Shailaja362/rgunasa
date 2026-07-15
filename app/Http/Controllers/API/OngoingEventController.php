<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\StudentEventRegistration;
use App\Models\StudentFeedback;
use App\Models\StudentUploadProof;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use function PHPUnit\Framework\isEmpty;

class OngoingEventController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        $student = auth('student-api')->user();
        $today = Carbon::today()->toDateString();
        if (!$student) {
            return response()->json([
                'status'  => 401,
                'message' => 'Unauthorized',
            ], 401);
        }

        $isFirstYearStudent = in_array((int) $student->semester, [1, 2]);
        $activeCount = StudentEventRegistration::where('student_id', $student->id)
            ->where('status', 1)
            ->whereHas('event', fn($q) => $q->where('publish', 1)->where('is_active', 'y'))
            ->count();
         $attendedCount = StudentEventRegistration::where('student_id', $student->id)
            ->where('status', 3)
            ->whereHas('get_event_attendance', fn($q) => $q->whereNotNull('entry_time')->whereNotNull('exit_time'))
            ->whereHas('event', fn($q) => $q->where('publish', 1)->where('is_active', 'y'))
            ->count();
        $totalRegistered = StudentEventRegistration::where('student_id', $student->id)
            ->whereHas('event', fn($q) => $q->where('publish', 1)->where('is_active', 'y'))
            ->count();
        $myUploads = StudentUploadProof::select('student_id', 'event_id')
            ->whereHas('event', fn($q) => $q->where('publish', 1)->where('is_active', 'y'))
            ->where('student_id', $student->id)
            ->groupBy('student_id', 'event_id')
            ->get();
        $pendingUploads = $totalRegistered - count($myUploads);
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
            ->get();
        $eventData = [];
        foreach ($ongoingEvents as $event) {
            foreach ($event->get_dep_events as $dept) {
                $eventDate = Carbon::parse($dept->event_date)->toDateString();
                $isCommonFirstYearEvent =
                    is_null($dept->programme_id) &&
                    is_null($dept->section) &&
                    is_null($dept->semester) &&
                    (is_null($dept->batch) || $dept->batch == $student->batch);
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
                    ->where('event_id', $dept->event_id)
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

                $eventDate = \Carbon\Carbon::parse($dept->event_date)->toDateString();
                $paidEventConflict = in_array($eventDate, $paidRegisteredDates);

                $canRegister =
                    !$permanentBlock &&
                    !$cooldownActive &&
                    $availableSeats > 0 &&
                    !$deadline->endOfDay()->isPast() &&
                    !$paidEventConflict;
                $text = '';

                if ($permanentBlock) {
                    $text = 'You have already registered for this event and cannot register again.';
                } elseif ($cooldownActive) {
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
                    'event_seats'       => $availableSeats,
                    'event_location'    => $event->location,
                    'event_date'        => Carbon::parse($dept->event_date)->format('F j, Y'),
                    'event_premium'     => $event->event_type === 'paid' ? 'paid' : 'free',
                    'event_register'    => $canRegister,
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


    public function registerEvent()
    {
        $student = auth('student-api')->user();

        if (!$student) {
            return response()->json([
                'status'  => 401,
                'message' => 'Unauthorized',
            ], 401);
        }

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

        // Ongoing Events Only
        $registered_event = StudentEventRegistration::with('event.get_dep_events.registrations','schedule.programme')->where('student_id', $student->id)
            ->whereHas('event', fn($q) => $q->where('publish', 1)->where('is_active', 'y'))
            ->get();


        // Build ongoing event data
        $eventData = [];

        foreach ($registered_event as $registration) {


            $event = $registration->event;

            if (!$event) {
                continue;
            }
            $start_time = $end_time = null;

            if (!empty($registration->schedule)) {
                if ($registration->schedule->is_reserve_date === 'y') {
                    $start_time = $event->reserve_start_time;
                    $end_time   = $event->reserve_end_time;
                } else {
                    $start_time = $event->start_time;
                    $end_time   = $event->end_time;
                }
            }

            $registeredCountQuery = StudentEventRegistration::where('event_schedule_id', $registration->event_schedule_id);

            $registeredCountQuery->whereHas('student', function ($q) use ($student) {
                $q->where('programme_id', $student->programme_id)
                    ->where('section', $student->section)
                    ->where('batch', $student->batch)
                    ->where('semester', $student->semester);
            });

                $registeredCount = $registeredCountQuery->count();
                $availableSeats = max(0, $registration->get_event_schedule?->seat_count - $registeredCount);


                $eventData[] = [
                    'event_id'          => $event->id,
                    'event_image'       => $event->banner_image
                        ? asset('storage/' . $event->banner_image)
                        : null,
                    'event_name'        => $event->title,
                    'event_description' => $event->description,
                    'event_start_time'  => $start_time
                        ? Carbon::parse($start_time)->format('g:i A')
                        : null,
                    'event_end_time'    => $end_time
                        ? Carbon::parse($end_time)->format('g:i A')
                        : null,
                    'event_seats'       => $availableSeats,
                    'event_location'    => $event->location,
                    'event_date'        => $registration->get_event_schedule?->event_date ? Carbon::parse($registration->get_event_schedule?->event_date)
                        ->format('F j, Y') : null,
                    'event_premium'     => $event->event_type === 'paid'
                        ? 'paid'
                        : 'free',
                    'student_name'      => $student->name,
                    'student_id'        => $student->id,
                    'student_email'     => $student->email,
                    'student_number'    => $student->mobile_number,
                ];
        }
        return response()->json([
            'status'                    => 200,
            'mgs'                       => 'Registered Successful',
            'active_registration_count' => $activeCount,
            'attended_events'           => $attendedCount,
            'pending_events'            => $pendingUploads,
            'data'                      => $eventData,
        ]);

    }

    public function completedEvent()
    {
        $student = auth('student-api')->user();

        if (!$student) {
            return response()->json([
                'status'  => 401,

                'message' => 'Unauthorized',
            ], 401);
        }

        // Active Registration Count
        $activeCount = StudentEventRegistration::where('student_id', $student->id)
            ->where('status', 1)
            ->whereHas('event', fn($q) => $q->where('publish', 1)->where('is_active', 'y'))
            ->count();

        // Attended Count
        $attendedCount = StudentEventRegistration::where('student_id', $student->id)
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
        $completed_event = StudentEventRegistration::with('get_event_attendance', 'event')->where('student_id', $student->id)
            ->whereHas('get_event_attendance', function ($query){
                $query->whereNotNull('entry_time')
                    ->whereNotNull('exit_time');
            })
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })
            ->get();
        // Build ongoing event data
        $eventData = [];

        foreach ($completed_event as $registration) {
            $event = $registration->event;
            if (!$event) {
                continue;
            }
            $start_time = $end_time = null;

            if (!empty($registration->schedule)) {
                if ($registration->schedule->is_reserve_date === 'y') {
                    $start_time = $event->reserve_start_time;
                    $end_time   = $event->reserve_end_time;
                } else {
                    $start_time = $event->start_time;
                    $end_time   = $event->end_time;
                }
            }

            $registeredCountQuery = StudentEventRegistration::where('event_schedule_id', $registration->event_schedule_id);

            $registeredCountQuery->whereHas('student', function ($q) use ($student) {
                $q->where('programme_id', $student->programme_id)
                    ->where('section', $student->section)
                    ->where('batch', $student->batch)
                    ->where('semester', $student->semester);
            });

            $registeredCount = $registeredCountQuery->count();
            $availableSeats = max(0, $registration->get_event_schedule?->seat_count - $registeredCount);

            $questions = getQuestions($event->is_technical_event == 'y' ? 'technical' : 'nonTechnical');
            $feedback = StudentFeedback::where('event_id', $event->id)
                ->where('event_schedule_id', $registration->get_event_schedule?->id)
                ->where('student_id', $student->id)
                ->first();
            if (!empty($feedback)) {
                $ratings = json_decode($feedback->ratings, true);
                foreach ($questions as &$question) {
                    if (isset($ratings[$question['key']])) {
                        $question['rating'] = (int) $ratings[$question['key']];
                    }
                }
            }
            $eventData[] = [
                'event_id'          => $event->id,
                'schedule_id'          => $registration->get_event_schedule->id ?? null,
                'event_image'       => $event->banner_image
                    ? asset('storage/' . $event->banner_image)
                    : null,
                'event_name'        => $event->title,
                'event_description' => $event->description,
                'event_start_time'  => $start_time
                    ? Carbon::parse($start_time)->format('g:i A')
                    : null,
                'event_end_time'    => $end_time
                    ? Carbon::parse($end_time)->format('g:i A')
                    : null,
                'event_seats'       => $availableSeats,
                'event_location'    => $event->location,
                'event_date'        => $registration->get_event_schedule?->event_date ? Carbon::parse($registration->get_event_schedule?->event_date)
                    ->format('F j, Y') : null,
                'event_premium'     => $event->event_type === 'paid'
                    ? 'paid'
                    : 'free',
                'student_name'      => $student->name,
                'student_id'        => $student->id,
                'student_email'     => $student->email,
                'student_number'    => $student->mobile_number,
                'event_type'        => $event->is_technical_event,
                'question'         =>  $questions,
                'upload_image_url'  => StudentUploadProof::where('event_id',$event->id)
                    ->where('event_schedule_id', $registration->get_event_schedule?->id)
                    ->where('student_id',$student->id)->get()->map(function($item){
                        return url('storage/'.$item->file_path);
                    }),
                'feed_back' => !empty($feedback->comments) ? $feedback->comments : null,
            ];
        }
        return response()->json([
            'status'                    => 200,
            'mgs'                       => 'Registered Successful',
            'active_registration_count' => $activeCount,
            'attended_events'           => $attendedCount,
            'pending_events'            => $pendingUploads,
            'data'                      => $eventData,
        ]);

    }


}
