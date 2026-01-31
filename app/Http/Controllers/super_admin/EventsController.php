<?php

namespace App\Http\Controllers\super_admin;

use App\Helpers\ActivityLog;
use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Department;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Faculty;
use App\Models\StudentEventRegistration;
use App\Models\Tasks;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventsController extends Controller
{
    // public function index(Request $request)
    // {
    //     $now = Carbon::now();
    //     $this->data['ongoingEvents'] = Event::with('registrations')
    //         ->whereDate('event_date', $now->toDateString())
    //         ->whereTime('start_time', '<=', $now->toTimeString())
    //         ->whereTime('end_time', '>=', $now->toTimeString())
    //         ->orderBy('start_time', 'asc')
    //         ->get();

    //     // Upcoming Events
    //     $this->data['upcomingEvents'] = Event::with('registrations')
    //         ->where(function ($query) use ($now) {
    //             $query->whereDate('event_date', '>', $now->toDateString())
    //                 ->orWhere(function ($q) use ($now) {
    //                     $q->whereDate('event_date', '=', $now->toDateString())
    //                         ->whereTime('start_time', '>', $now->toTimeString());
    //                 });
    //         })
    //         ->orderBy('event_date', 'asc')
    //         ->orderBy('start_time', 'asc')
    //         ->get();
    //     $this->data['registeredEvents'] = StudentEventRegistration::with('event')
    //         ->get();

    //     $this->data['completedEvents'] = StudentEventRegistration::with('event')
    //         ->whereHas('event', function ($query) use ($now) {
    //             $query->where('event_date', '<', $now->toDateString());
    //         })
    //         ->get();
    //     return view('super_admin.event_index')->with($this->data);
    // }

    public function index(Request $request)
    {
        $now = Carbon::now();

        $this->data['ongoingEvents'] = Event::with(['registrations', 'schedules' => function ($q) use ($now) {
            $q->whereDate('event_date', $now->toDateString());
        }])->whereHas('schedules', function ($q) use ($now) {
            $q->whereDate('event_date', $now->toDateString());
        })
            ->whereTime('start_time', '<=', $now->toTimeString())
            ->whereTime('end_time', '>=', $now->toTimeString())
            ->get();

        // Upcoming Events
        $this->data['upcomingEvents'] = Event::with(['registrations', 'schedules' => function ($q) use ($now) {
            $q->whereDate('event_date', '>', $now->toDateString())
                ->orWhere(function ($q2) use ($now) {
                    $q2->whereDate('event_date', $now->toDateString());
                });
        }])->whereHas('schedules', function ($q) use ($now) {
            $q->whereDate('event_date', '>', $now->toDateString())
                ->orWhere(function ($q2) use ($now) {
                    $q2->whereDate('event_date', $now->toDateString());
                });
        })
            ->whereTime('start_time', '>', $now->toTimeString())->get();

        // Completed Events
        $this->data['completedEvents'] = Event::with(['registrations', 'schedules' => function ($q) use ($now) {
            $q->whereDate('event_date', '<', $now->toDateString())
                ->orWhere(function ($q2) use ($now) {
                    $q2->whereDate('event_date', $now->toDateString());
                });
        }])->whereHas('schedules', function ($q) use ($now) {
            $q->whereDate('event_date', '<', $now->toDateString())
                ->orWhere(function ($q2) use ($now) {
                    $q2->whereDate('event_date', $now->toDateString());
                });
            })
            ->whereTime('end_time', '<', $now->toTimeString())->get();
        $this->data['registeredEvents'] = StudentEventRegistration::with('event.schedules')->get();
        return view('super_admin.event_index')->with($this->data);
    }



    public function createEvent(Request $request)
    {
        $this->data['faculty'] = Faculty::get();
        $this->data['club'] = Club::get();
        $this->data['departments'] = Department::get();
        if ($request->event_id) {
            $eventId = decrypt($request->event_id);
            $this->data['edit_event'] = Event::where('id', $eventId)->first();
            $this->data['edit_faculty'] = Faculty::where('id', $this->data['edit_event']->faculty_id)->first();
        }
        if ($request->ajax()) {
            if ($request->get_programme_officer) {
                $get_faculty = Club::with('get_faculty')->where('id', $request->clubId)->first();
                return response()->json([
                    'success' => true,
                    'faculty' => $get_faculty
                ]);
            }
        }
        return view('super_admin.create_event')->with($this->data);
    }

    public function eventlist(Request $request)
    {
        $adminId = Auth::guard('admin')->id();
        $this->data['events'] = Event::with('get_faculty')->paginate(10);
        $this->data['tasks'] = Tasks::with('get_admin', 'get_task_images', 'get_event')->where('admin_id', $adminId)->get();
        return view('super_admin.event_list')->with($this->data);
    }

    public function saveEvent(Request $request)
    {

        $rules = [
            'event_title'   => 'required',
            'club_id'   => 'required',
            'programme_officer'   => 'required',
            'description'   => 'required',
            'start_time'   => 'required',
            'end_time'   => 'required',
            'location'   => 'required',
            'session'   => 'required',
            'eligibility'   => 'required',
            'registration_deadline'   => 'required',
            'contact_person'   => 'required',
            'contact_email'   => 'required',
            'event_type'   => 'required',
            'duration_months' => 'required',
        ];

        if ($request['event_type'] == 'paid') {
            $rules['price'] = 'required';
        }

        if (empty($request['event_id']) && !$request->has('old_banner')) {
            $rules['banner_image'] = 'required|image|mimes:jpeg,png,jpg';
        } else if ($request->hasFile('banner_image')) {
            $rules['banner_image'] = 'image|mimes:jpeg,png,jpg';
        }
        $request->validate($rules);
        try {

            if (!empty($request['event_id'])) {
                $message = 'Event Updated successfully';
                $event = Event::find($request['event_id']);
            } else {
                $event = new Event();
                $message = 'Event saved successfully';
            }

            if (!empty($request['task_id'])) {
                $taskId = decrypt($request['task_id']);
            } else {
                $taskId = null;
            }

            if ($request->hasFile('banner_image')) {
                $file = $request->file('banner_image');
                $img_name = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('event_banner', $img_name, 'public');
                $event->banner_image = 'event_banner/' . $img_name;
            } elseif ($request->has('old_banner')) {
                $event->banner_image = $request->old_banner;
            }
            $adminId = Auth::guard('admin')->id();
            $event->club_id  = $request['club_id'];
            $event->task_id = $taskId;
            $event->created_by =  $adminId ?? '';
            $event->price = $request['price'] ?? 0;
            $event->faculty_id = $request['programme_officer'] ?? '';
            $event->title  = $request['event_title'] ?? '';
            $event->description = $request['description'] ?? '';
            $event->start_time  = $request['start_time'] ?? '';
            $event->end_time = $request['end_time']  ?? '';
            $event->reserve_start_time  = $request['reserve_start_time'] ?? '';
            $event->reserve_end_time = $request['reserve_end_time']  ?? '';
            $event->event_type = $request['event_type'] ?? '';
            $event->location  = $request['location'] ?? '';
            $event->session = $request['session']  ?? '';
            $event->eligibility_criteria = $request['eligibility']  ?? '';
            $event->end_registration = $request['registration_deadline']  ?? '';
            $event->contact_person = $request['contact_person']  ?? '';
            $event->contact_email = $request['contact_email']  ?? '';
            $event->duration_months = $request['duration_months'];
            $event->save();

            EventSchedule::where(['event_id' => $event->id])->delete();

            foreach ($request->departments as $schedule) {
                $exists_schedule = EventSchedule::where(['event_id' => $event->id,
                      'department_id' => $schedule['department_id'], 'section' => $schedule['section'] ,
                      'event_date' => $schedule['event_date']])->first();
                if(empty($exists_schedule)){
                    $create_event_schedule = new EventSchedule();
                    $create_event_schedule->event_id = $event->id;
                    $create_event_schedule->department_id = $schedule['department_id'];
                    $create_event_schedule->section = $schedule['section'];
                    $create_event_schedule->event_date = $schedule['event_date'];
                    $create_event_schedule->reserve_date = $schedule['reserve_date'];
                    $create_event_schedule->seat_count = $schedule['seat_count'];
                    $create_event_schedule->save();
                }
            }

            if ($event && !empty($taskId)) {
                $get_task = Tasks::where('id', $taskId)->first();
                if ($get_task) {
                    ActivityLog::add($get_task->title . ' - Task Completed', auth('admin')->user());
                    $update = Tasks::where('id', $taskId)->update([
                        'status' => 'completed'
                    ]);
                }
            }

            if (!empty($request['event_id'])) {
                ActivityLog::add($event->title . ' - Event Updated', auth('admin')->user());
            } else {
                ActivityLog::add($event->title . ' - New Event Created', auth('admin')->user());
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'event' => $event,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'Failed to save event',
            ], 500);
        }
    }
}
