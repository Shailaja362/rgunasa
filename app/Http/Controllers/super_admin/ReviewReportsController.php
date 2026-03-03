<?php

namespace App\Http\Controllers\super_admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewReportsController extends Controller
{
    public function index()
    {
        $adminId = Auth::guard('admin')->id();
        $this->data['events'] = Event::with('registrations')
        ->where('created_by', $adminId)
        ->where(['publish' => 1,
        'is_active' => 'y'
        ])
        ->get();
        $this->data['reports'] = EventReport::with('creator','get_event_image', 'get_event.get_task', 'schedule.department','get_event')
            ->whereHas('get_event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })->get();
        return view('super_admin.review_reports_index')->with($this->data);
    }
}
