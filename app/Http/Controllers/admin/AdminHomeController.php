<?php

namespace App\Http\Controllers\admin;

use App\Models\Event;
use App\Models\Tasks;
use App\Models\EventReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\StudentEventRegistration;

class AdminHomeController extends Controller
{
    public function index(Request $request)
    {
        if(!empty(session()->get('super_admin'))){
            return redirect()->route('admin.login');
                // ->withErrors(['password' => 'Either Email/Password is incorrect'])
                // ->withInput($request->only('email'));
        }

        $adminId = Auth::guard('admin')->id();
        $this->data['events'] = Event::with('registrations')
            ->where('created_by', $adminId)
            ->orderBy('created_at', 'DESC')
            ->get();
        $today = now()->toDateString();

        $this->data['upcoming_events'] = Event::where('created_by', $adminId)
            ->where('created_by', $adminId)
            ->whereHas('schedules', function ($query) use ($today) {
                $query->whereDate('event_date', '>=', $today);
            })
            ->with(['schedules' => function ($query) use ($today) {
                $query->whereDate('event_date', '>=', $today)->orderBy('event_date', 'asc');
            }])
            ->get();

        $this->data['pending_approvals'] = StudentEventRegistration::with('event')
                                             ->whereHas('event' , function($query) use($adminId) {
                                                 $query->where('created_by',  $adminId);
                                             })
                                             ->where('status',1)
                                             ->get();
        $this->data['submitted_reports'] = EventReport::where('created_by', $adminId)
            ->select('event_id', DB::raw('COUNT(*) as total_reports'))
            ->groupBy('event_id')
            ->get();
        $this->data['total_tasks'] = Tasks::where('admin_id', $adminId)->count();
        $this->data['pending_tasks'] = Tasks::where('admin_id', $adminId)->where('status','pending')->count();
        $this->data['completed_tasks'] = Tasks::where('admin_id', $adminId)->whereNot('status', 'pending')->count();
        $this->data['approved_tasks'] = Tasks::where('admin_id', $adminId)->where('status', 'accepted')->count();
        return view('admin.admin_home_index')->with($this->data);
    }
}
