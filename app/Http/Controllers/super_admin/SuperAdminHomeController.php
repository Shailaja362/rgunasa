<?php

namespace App\Http\Controllers\super_admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Admin;
use App\Models\Event;
use App\Models\Student;
use App\Models\Tasks;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminHomeController extends Controller
{
    public function index(Request $request)
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek   = Carbon::now()->endOfWeek();
        $adminId = Auth::guard('admin')->id();

        // Total Upcoming Events (event date > today)
        $this->data['upcomingEvents'] = Event::whereHas('schedules', function ($q) {
            $q->whereDate('event_date', '>', now());
        })
        ->where('publish',1)
        ->count();

        // Ongoing Events (event date = today)
        $this->data['ongoingEvents'] = Event::whereHas('schedules', function ($q) {
            $q->whereDate('event_date', now());
        })
        ->where('publish', 1)
        ->count();

        // Total Admins with role_id 2
        $this->data['totalAdmins'] = Admin::where('role_id', 2)->count();

        // Total Students
        $this->data['totalStudents'] = Student::count();

        // Upcoming Events This Week
        $this->data['upcomingEventsThisWeek'] = Event::whereHas('schedules', function ($q) use ($startOfWeek, $endOfWeek) {
            $q->whereBetween('event_date', [$startOfWeek, $endOfWeek])
                ->whereDate('event_date', '>', now());
        })
            ->where('publish', 1)
            ->count();

        // Admins Created This Month
        $this->data['adminsThisMonth'] = Admin::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Students Created This Month
        $this->data['studentsThisMonth'] = Student::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Submitted Reports = tasks where event has report
        $this->data['submittedReports'] = Tasks::where('created_by', $adminId)
            ->whereHas('get_event.get_report')
            ->count();

        // Pending Reports = tasks whose event exists but report not created
        $this->data['pendingReports'] = Tasks::where('created_by', $adminId)
            ->whereHas('get_event') // event exists
            ->whereDoesntHave('get_event.get_report') // report missing
            ->count();

        // This Week Submitted Reports
        $this->data['thisweeksubmittedReports'] = Tasks::where('created_by', $adminId)
            ->whereHas('get_event.schedules', function ($q) use ($startOfWeek, $endOfWeek) {
                $q->whereBetween('event_date', [$startOfWeek, $endOfWeek])
                    ->whereHas('get_report');
            })
            ->count();

        // This Week Pending Reports
        $this->data['thisweekpendingReports'] = Tasks::where('created_by', $adminId)
            ->whereHas('get_event.schedules', function ($q) use ($startOfWeek, $endOfWeek) {
                $q->whereBetween('event_date', [$startOfWeek, $endOfWeek])
                    ->whereDoesntHave('get_report');
            })
            ->count();

        // Today's Activities
        $this->data['activities'] = Activity::whereDate('created_at', Carbon::today())
            ->orderBy('id', 'DESC')
            ->get();

        return view('super_admin.super_admin_home')->with($this->data);
    }
}
