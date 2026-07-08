<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Notification;
use App\Models\Student;
use App\Models\StudentEventRegistration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{


    public function details(){
        $student = auth('student-api')->user();
        return response()->json([
            "data" => [
                    "user_image"      => $student->profile_pic ? url('/storage/' . $student->profile_pic) : null,
                    "user_name"       => $student->name,
                    "mobile_number"   => $student->mobile_number,
                    "user_email"    => $student->email,
                    "user_id"    => $student->id,
            ],
            'status' => 200,
            'message' => "Student Details Fetched Successfully"
        ]);

    }

    public function viewCertificate()
    {
        $student = auth('student-api')->user();

        $completedEvents = StudentEventRegistration::with([
            'event',
            'student',
            'get_event_attendance'
        ])
            ->where('student_id', $student->id)
            ->whereNotNull('grade')
            ->where('grade', '!=', 'd')
            ->whereHas('get_event_attendance', function ($query) {
                $query->whereNotNull('entry_time')
                    ->whereNotNull('exit_time');
            })
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })
            ->get()
            ->map(function ($registration) {

                $event = $registration->event;
                $student = $registration->student;

                return [
                    'student_id'         => $student->id,
                    'event_id'           => $event->id,
                    'student_name'       => $student->name,
                    'event_name'         => $event->title,
                    'event_description'  => $event->description,
                    'certificate'        => $event->certificate,

                    // Download API URL
                    'certificate_url' => route('student.certificate.download', [
                        'event_id'   => $event->id,
                        'student_id' => $student->id,
                    ]),
                ];
            });

        return response()->json([
            'status'  => 200,
            'message' => 'Student Certificate Fetched Successfully',
            'data'    => $completedEvents,
        ]);
    }

    public function downloadCertificate(Request $request)
    {
        $event = Event::with('get_admin')->findOrFail($request->event_id);

        $registration = StudentEventRegistration::with('event')
            ->where('event_id', $event->id)
            ->where('student_id', $request->student_id)
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })
            ->firstOrFail();

        $student = Student::with('get_department')
            ->findOrFail($request->student_id);

        $template = $event->is_technical_event == "y"
            ? 'student.certificates.paid_certificate'
            : 'student.certificates.free_certificate';

        $pdf = Pdf::loadView($template, [
            'event'        => $event,
            'student'      => $student,
            'registration' => $registration,
        ])->setPaper('a4', 'landscape');

        $filename = 'certificate-' . time() . '-' . $student->id . '.pdf';

        Storage::disk('public')->put(
            'certificates/' . $filename,
            $pdf->output()
        );

        return response()->json([
            'status' => true,
            'url' => asset('/storage/certificates/' . $filename),
        ]);
    }

    public function notificationList()
    {
        if (!auth('student-api')->user()) {
            return response()->json([
                'success' => false,
                'message' => 'You need to login first'
            ]);
        }

        $notification = Notification::where('user_id', auth('student-api')->id())->orderBy('created_at', 'desc')->get()->map(function ($list) {
            return [
                'notification_id' => $list->id,
                'notification_title' => $list->title,
                'notification_description' => $list->description,
                'created_date' => $list->created_at,
                'read_status' => !($list->status === 0),
            ];
        });
        return response()->json([
            'status' => 200,
            'message' => 'Product View',
            'data' => $notification->values()
        ]);
    }

    public function deleteNotification(Request $request)
    {

        if (!auth('student-api')->user()) {
            return response()->json([
                'success' => false,
                'message' => 'You need to login first'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|array|min:1',
        ]);

        // Check if validation failed
        if ($validator->fails()) {
            return response()->json([
                'status' => 409,
                'message' => $validator->errors()->first(),
            ], 409);
        }
        foreach ($request['notification_id'] as $notification_id) {
            Notification::where('user_id', auth('student-api')->id())->where('id', $notification_id)->delete();
        }
        return response()->json([
            'status' => 200,
            'message' => 'Notification Deleted Successfully',
        ]);
    }

    public function readNotificationAll(Request $request)
    {
        if (!auth('student-api')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'You need to login first',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|array|min:1',
            'notification_id.*' => 'integer|exists:notifications,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 409,
                'message' => $validator->errors()->first(),
            ], 409);
        }

        Notification::where('user_id', auth('student-api')->id())
            ->whereIn('id', $request->notification_id)
            ->update(['status' => 1]);

        return response()->json([
            'status' => 200,
            'message' => 'Notification read status updated successfully.',
        ]);
    }
}
