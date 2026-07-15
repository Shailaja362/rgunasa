<?php

namespace App\Http\Controllers\super_admin;

use App\Helpers\ActivityLog;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Student;
use App\Models\StudentEventRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentApprovalController extends Controller
{
    public function index()
    {
        if (!empty(session()->get('admin'))) {
            $adminId = Auth::guard('admin')->id();
            $this->data['registeredEvents'] = StudentEventRegistration::with('event', 'student', 'get_student_proof')
                ->whereHas('event', function ($query) use ($adminId) {
                    $query->where('created_by', $adminId);
                })
                ->get();
            $this->data['pending'] = StudentEventRegistration::with('event')->whereHas('event', function ($query) use ($adminId) {
                $query->where('created_by', $adminId);
            })->where('status', 1)->count();
            $this->data['present'] = StudentEventRegistration::with('event')->whereHas('event', function ($query) use ($adminId) {
                $query->where('created_by', $adminId);
            })->whereNotNull('grade')->count();
            $this->data['absent'] = StudentEventRegistration::with('event')->whereHas('event', function ($query) use ($adminId) {
                $query->where('created_by', $adminId);
            })->where('status', 4)->count();
            $this->data['total_applied_event'] = StudentEventRegistration::with('event', 'student', 'get_student_proof')->whereHas('event', function ($query) use ($adminId) {
                $query->where('created_by', $adminId);
            })->count();
        } else if (!empty(session()->get('super_admin'))) {
            $this->data['registeredEvents'] = StudentEventRegistration::with('event', 'student', 'get_student_proof')->get();
            $this->data['pending'] = StudentEventRegistration::where('status', 1)->count();
            $this->data['present'] = StudentEventRegistration::whereNotNull('grade')->count();
            $this->data['absent'] = StudentEventRegistration::where('status', 4)->count();
            $this->data['total_applied_event'] = StudentEventRegistration::with('event', 'student', 'get_student_proof')->count();
        }
        $this->data['students'] = Student::get();
        $this->data['event'] = Event::get();
        return view('super_admin.student_approval_index')->with($this->data);
    }

    public function studentEventApproval(Request $request)
    {
        $getstudent = StudentEventRegistration::with(['student', 'event'])
            ->where('id', $request->id)
            ->first();

        $update = StudentEventRegistration::where('id', $request->id)->update([
            'status' => $request->action
        ]);
        // Set status label safely
        if ($request->action == 2) {
            $status = 'Approved';
        } elseif ($request->action == 4) {
            $status = 'Rejected';
        } else {
            $status = 'Updated';
        }

        if ($update) {
            $studentName = $getstudent->student->name ?? 'Unknown Student';
            $eventName = $getstudent->event->title ?? 'Event';

            ActivityLog::add(
                $studentName . ' - ' . $eventName . ' ' . $status,
                auth('admin')->user()
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully!'
        ]);
    }
}
