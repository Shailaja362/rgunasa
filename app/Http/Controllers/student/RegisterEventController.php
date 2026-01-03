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
use Illuminate\Support\Facades\Auth;
use App\Models\StudentEventRegistration;

class RegisterEventController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        $this->data['events'] = Event::get();
        $student = session()->get('student');
        $this->data['studentId'] = $student->id;
        $myuploads = StudentUploadProof::select('student_id', 'event_id')
            ->where('student_id', $student->id)
            ->groupBy('student_id', 'event_id')
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
            ->where('status',3)
            ->count();
        $this->data['studentRegistrations'] = StudentEventRegistration::where('student_id', $student->id)
            ->with('event') // eager load event for date and type
            ->get();
        return view('student.register_event_index')->with($this->data);
    }

    // public function studentRegisterEvent(Request $request)
    // {
    //     $request->validate([
    //         'student_id' => 'required|string|max:255',
    //         'event'  => 'required',
    //     ]);

    //     try {
    //         $register_event = StudentEventRegistration::where(['student_id' => $request->stu_id, 'event_id' => $request->event_id])->first();

    //         if (!$register_event) {

    //             $register = new StudentEventRegistration();
    //             $register->student_id    = $request->stu_id;
    //             $register->event_id      = $request->event_id ?? null;
    //             $register->status        = 1;
    //             $register->save();

    //             if (!empty($request['report_id'])) {
    //                 $user = auth('student')->user();
    //                 $event = Event::where('id', $request->event_id)->first();
    //                 ActivityLog::add($user->name . ' - ' . $event->title . " - Event Registered", $user);
    //             }
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Event Registered Successfully!',
    //         ]);
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to Register Event',
    //             'error'   => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function studentRegisterEvent(Request $request)
    {
        $request->validate([
            'stu_id'   => 'required',
            'event_id' => 'required',
        ]);

        try {
            $event = Event::findOrFail($request->event_id);

            // Get last registration
            $lastRegistration = StudentEventRegistration::where([
                'student_id' => $request->stu_id,
                'event_id'   => $event->id
            ])
                ->orderBy('registered_at', 'desc')
                ->first();

            if ($lastRegistration) {

                // ❌ Case 1: Duration NOT set → block duplicate forever
                if (empty($event->duration_months) || $event->duration_months == 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are already registered for this event.',
                    ]);
                }

                // ❌ Case 2: Duration set → check time limit
                $nextAllowedDate = Carbon::parse($lastRegistration->registered_at)
                    ->addMonths($event->duration_months);

                if (Carbon::now()->lt($nextAllowedDate)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can register again after ' . $nextAllowedDate->format('d M Y'),
                    ]);
                }
            }

            // ✅ Allow registration
            $register_student = new StudentEventRegistration();
            $register_student->student_id     = $request->stu_id;
            $register_student->event_id       = $event->id;
            $register_student->status         = 1;
            $register_student->registered_at  = Carbon::now();
            $register_student->save();

            return response()->json([
                'success' => true,
                'message' => 'Registration successful.',
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Registration Failed',
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
