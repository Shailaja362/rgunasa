<?php

namespace App\Http\Controllers\student;

use Exception;
use Carbon\Carbon;
use App\Models\Event;
use App\Models\Student;
use App\Helpers\ActivityLog;
use Illuminate\Http\Request;
use App\Models\StudentUploadProof;
use App\Http\Controllers\Controller;
use App\Models\EventSchedule;
use Illuminate\Support\Facades\Auth;
use App\Models\StudentEventRegistration;
use Illuminate\Support\Facades\DB;

class RegisterEventController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        $this->data['events'] = Event::where('publish', 1)->get();
        $student = session()->get('student');
        $this->data['student'] = $student;
        $this->data['studentId'] = $student->id;
        $myuploads = StudentUploadProof::select('student_id', 'event_id')
            ->where('student_id', $student->id)
            ->groupBy('student_id', 'event_id')
            ->get();
        $this->data['ongoingEvents'] =  Event::whereHas('get_dep_events', function ($q) use ($student) {
            $q->where('programme_id', $student->programme_id)
                ->where('section', $student->section)
                ->where('batch', $student->batch)
                ->where('semester', $student->semester)
                ->where('event_date', Carbon::now()->toDateString());
        })
            ->with(['get_dep_events' => function ($q) use ($student) {
                $q->where('programme_id', $student->programme_id)
                    ->where('section', $student->section)
                    ->where('batch', $student->batch)
                    ->where('semester', $student->semester)
                    ->where('event_date', Carbon::now()->toDateString());
            }, 'get_dep_events.registrations'])
            ->where('publish', 1)
            ->get();
        // Upcoming Events
        $this->data['upcomingEvents'] =  Event::whereHas('get_dep_events', function ($q) use ($student) {
            $q->where('programme_id', $student->programme_id)
                ->where('section', $student->section)
                ->where('batch', $student->batch)
                ->where('semester', $student->semester);
            // ->where('event_date', '>=', Carbon::now()->toDateString()); // Only future dates
        })
            ->with(['get_dep_events' => function ($q) use ($student) {
                $q->where('programme_id', $student->programme_id)
                    ->where('section', $student->section)
                    ->where('batch', $student->batch)
                    ->where('semester', $student->semester)
                    // ->where('event_date', '>=', Carbon::now()->toDateString())
                    ->orderBy('event_date', 'asc');
            }, 'get_dep_events.registrations'])
            ->where('publish', 1)
            ->get();

        $this->data['activeCount'] = StudentEventRegistration::where('student_id', $student->id)
            ->where('status', 1)
            ->count();
        $activecount = StudentEventRegistration::where('student_id', $student->id)->count();
        $this->data['pending_uploads'] =  $activecount - count($myuploads);
        $this->data['attendedCount'] = StudentEventRegistration::with('get_event_attendance')->where('student_id', $student->id)
            ->whereHas('get_event_attendance', function ($query) use ($now) {
                $query->whereNotNull('entry_time')
                    ->whereNotNull('exit_time');
            })
            ->where('status', 3)
            ->count();
        $this->data['studentRegistrations'] = StudentEventRegistration::where('student_id', $student->id)
            ->with('event') // eager load event for date and type
            ->get();
        return view('student.register_event_index')->with($this->data);
    }

    public function studentRegisterEvent(Request $request)
    {
        $request->validate([
            'stu_id'      => 'required|exists:students,id',
            'event_id'    => 'required|exists:events,id',
            'schedule_id' => 'required|exists:event_schedules,id',
        ]);
        DB::beginTransaction();
        try {
            $event = Event::findOrFail($request->event_id);
            $schedule = EventSchedule::findOrFail($request->schedule_id);
            $student = Student::where('id',$request->stu_id)->first();
            $lastRegistration = StudentEventRegistration::where([
                'student_id' => $request->stu_id,
                'event_id'   => $event->id,
                'event_schedule_id' => $schedule->id
            ])
                ->orderBy('registered_at', 'desc')
                ->first();
            if ($lastRegistration && (empty($event->duration_months) || $event->duration_months == 0)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'You are already registered for this event.',
                ]);
            }

            if ($lastRegistration && $event->duration_months > 0) {
                $nextAllowedDate = Carbon::parse($lastRegistration->registered_at)
                    ->addMonths($event->duration_months);
                if (Carbon::now()->lt($nextAllowedDate)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'You can register again after ' . $nextAllowedDate->format('d M Y'),
                    ]);
                }
            }

            $registeredCount = StudentEventRegistration::where('event_schedule_id', $schedule->id)
                ->whereHas('student', function ($query) use ($student) {
                    $query
                        ->where('programme_id', $student->programme_id)
                        ->where('section', $student->section)
                        ->where('batch', $student->batch)
                        ->where('semester', $student->semester);
                })
                ->count();
            if ($registeredCount >= $schedule->seat_count) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'This event seat is already full.',
                ]);
            }

            // Register student
            StudentEventRegistration::create([
                'student_id'        => $request->stu_id,
                'event_id'          => $event->id,
                'event_schedule_id' => $schedule->id,
                'status'            => 1,
                'registered_at'     => Carbon::now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registration successful.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Registration failed.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function getStudent(Request $request)
    {
        if ($request->ajax() && $request->get_student) {
            $student = session()->get('student');
            $event = Event::where('id', $request->event_id)->first();
            return response()->json([
                'success' => true,
                'student' => $student,
                'event' => $event
            ]);
        }
    }
}
