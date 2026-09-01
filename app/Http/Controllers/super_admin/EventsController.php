<?php

namespace App\Http\Controllers\super_admin;

use App\Helpers\ActivityLog;
use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Club;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Faculty;
use App\Models\Programme;
use App\Models\Tasks;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EventsController extends Controller
{

    public function index(Request $request)
    {
        $from  = $request->from_date;
        $to    = $request->to_date;
        $programmeId  = $request->programme_id;
        $today = now()->toDateString();
        $baseQuery = EventSchedule::query()->with([
            'event',
            'programme',
            'registrations.student.get_department'
        ])
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            });
        if ($from && $to) {
            $baseQuery->whereBetween('event_date', [$from, $to]);
        } elseif ($from) {
            $baseQuery->whereDate('event_date', '>=', $from);
        } elseif ($to) {
            $baseQuery->whereDate('event_date', '<=', $to);
        }

        /*
    |--------------------------------------------------------------------------
    | Programme Filter (NEW)
    |--------------------------------------------------------------------------
    */
        if ($programmeId) {
            $baseQuery->openToProgramme($programmeId);
        }

        $this->data['upcomingEvents'] = (clone $baseQuery)
            ->whereDate('event_date', '>', $today)
            ->orderBy('event_date')
            ->get();
        $this->data['ongoingEvents'] = (clone $baseQuery)
            ->whereDate('event_date', '=', $today)
            ->get();
        $this->data['completedEvents'] = (clone $baseQuery)
            ->whereDate('event_date', '<', $today)
            ->orderByDesc('event_date')
            ->paginate(10)
            ->appends($request->query());
        $this->data['completedEvents']->getCollection()->transform(function ($schedule) {
            $departments = $schedule->registrations
                ->pluck('student.get_department.name')
                ->filter()
                ->unique()
                ->values();
            $schedule->departments = $departments;
            return $schedule;
        });

        $this->data['registeredEvents'] = EventSchedule::select(
            'event_schedules.id',
            'event_schedules.event_id',
            'event_schedules.event_date',
            'event_schedules.programme_id',
            'event_schedules.section',
            DB::raw('COUNT(DISTINCT student_event_registrations.student_id) as total_students')
        )
            ->join(
                'student_event_registrations',
                'event_schedules.id',
                '=',
                'student_event_registrations.event_schedule_id'
            )
            ->when($from || $to, function ($query) use ($from, $to) {
                if ($from && $to) {
                    $query->whereBetween('event_schedules.event_date', [$from, $to]);
                } elseif ($from) {
                    $query->whereDate('event_schedules.event_date', '>=', $from);
                } elseif ($to) {
                    $query->whereDate('event_schedules.event_date', '<=', $to);
                }
            })
            ->when($programmeId, function ($query) use ($programmeId) {
                $query->openToProgramme($programmeId);
            })
            ->groupBy(
                'event_schedules.id',
                'event_schedules.event_id',
                'event_schedules.event_date',
                'event_schedules.programme_id',
                'event_schedules.section',
            )
            ->with('event', 'programme')
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })
            ->orderByDesc('event_schedules.event_date')
            ->paginate(10)
            ->appends($request->query());
        $this->data['programmes'] = Programme::get();
        return view('super_admin.event_index', $this->data);
    }

    public function createEvent(Request $request)
    {
        $this->data['faculty'] = Faculty::get();
        $this->data['club'] = Club::get();
        $this->data['programmes'] = Programme::get();
        $this->data['batches'] = Batch::orderBy('name')->get(['id', 'name']);
        if ($request->event_id) {
            $eventId = decrypt($request->event_id);
            $this->data['edit_event'] = Event::where('id', $eventId)->first();
            $this->data['edit_faculty'] = Faculty::where('id', $this->data['edit_event']->faculty_id)->first();
        } else {
            $this->data['edit_event'] = null;
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
        if (!empty(session()->get('super_admin'))) {
            $query = Event::with('get_faculty', 'schedules', 'get_task', 'schedules.registrations');
        } else {
            $query = Event::with('get_faculty', 'schedules', 'get_task', 'schedules.registrations')
                      ->where('created_by', $adminId);
        }

         if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        if ($request->filled('programme_officer')) {
            $query->where('faculty_id', $request->programme_officer);
        }
        $this->data['events'] = $query
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();
        $this->data['programme_officer'] = Faculty::get();
        $this->data['tasks'] = Tasks::with('get_admin', 'get_task_images', 'get_event')->where('admin_id', $adminId)->get();
        return view('super_admin.event_list')->with($this->data);
    }

    public function saveEvent(Request $request)
    {
        try {
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
                'duration_days' => 'required',
                'departments' => 'required|array|min:1',
                'departments.*.programme_id' => 'required|array|min:1',
                'departments.*.programme_id.*' => 'required|exists:programmes,id',
                'departments.*.section' => 'required|array|min:1',
                'departments.*.section.*' => 'required|in:a,b,c,d,e,f,r',
                'departments.*.event_date' => 'required|date_format:d/m/Y',
                'departments.*.is_reserve_date' => 'nullable|in:y,n',
                'departments.*.seat_count' => 'required|integer|min:1',
                'departments.*.batch' => 'required|array|min:1',
                'departments.*.batch.*' => 'exists:batches,name',
                'departments.*.semester' => 'required|array|min:1',
                'departments.*.semester.*' => 'in:1,2,3,4,5,6,7,8',
                'departments.*.credit_points' => 'required|numeric|min:0|max:4',
            ];

            if ($request['event_type'] == 'paid') {
                $rules['price'] = 'required';
            }

            if (empty($request['event_id']) && !$request->has('old_banner')) {
                $rules['banner_image'] = 'required|image|mimes:jpg,jpeg,png,webp|max:2048';
            } else if ($request->hasFile('banner_image')) {
                $rules['banner_image'] = 'image|mimes:jpg,jpeg,png,webp|max:2048';
            }

            $request->validate($rules);

            DB::beginTransaction();

            if (!empty($request['event_id'])) {
                $message = 'Event Updated successfully';
                $event = Event::find($request['event_id']);
            } else {
                $event = new Event();
                $adminId = Auth::guard('admin')->id();
                $event->created_by =  $adminId ?? '';
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

            $event->club_id  = $request['club_id'];
            $event->task_id = $taskId;
            $event->price = $request['price'] ?? 0;
            $event->faculty_id = $request['programme_officer'] ?? '';
            $event->title  = $request['event_title'] ?? '';
            $event->description = $request['description'] ?? '';
            $event->start_time  = $request['start_time'] ?? '';
            $event->end_time = $request['end_time']  ?? '';
            $event->reserve_start_time  = $request['reserve_start_time'] ?? null;
            $event->reserve_end_time = $request['reserve_end_time']  ?? null;
            $event->event_type = $request['event_type'] ?? '';
            $event->location  = $request['location'] ?? '';
            $event->session = $request['session']  ?? '';
            $event->eligibility_criteria = $request['eligibility']  ?? '';
            $event->end_registration = Carbon::createFromFormat('d/m/Y', $request['registration_deadline'])
                ->format('Y/m/d');
            $event->contact_person = $request['contact_person']  ?? '';
            $event->contact_email = $request['contact_email']  ?? '';
            $event->duration_days = $request['duration_days'];
            $event->is_technical_event = $request['is_technical_event'] ?? '';
            $event->is_active = $request['is_active'] ?? 'y';
            $event->save();

            $submittedScheduleIds = collect($request->departments)
                ->pluck('schedule_id')
                ->filter()
                ->values();

            EventSchedule::where('event_id', $event->id)
                ->whereNotIn('id', $submittedScheduleIds)
                ->delete();

            foreach ($request->departments as $schedule) {
                // Multiple selected batches/semesters are stored as a comma-separated
                // list on a single row, so seat_count is one shared quota for the
                // whole department entry rather than being duplicated per combination.
                $batches = !empty($schedule['batch']) ? array_values(array_unique((array) $schedule['batch'])) : [];
                $semesters = !empty($schedule['semester']) ? array_values(array_unique((array) $schedule['semester'])) : [];
                $programmeIds = !empty($schedule['programme_id']) ? array_values(array_unique((array) $schedule['programme_id'])) : [];
                $sections = !empty($schedule['section']) ? array_values(array_unique((array) $schedule['section'])) : [];

                $data = [
                    'event_id'        => $event->id,
                    'programme_id'    => !empty($programmeIds) ? implode(',', $programmeIds) : null,
                    'section'         => !empty($sections) ? implode(',', $sections) : null,
                    'event_date'      => Carbon::createFromFormat('d/m/Y', $schedule['event_date'])->format('Y-m-d'),
                    'is_reserve_date' => $schedule['is_reserve_date'] ?? 'n',
                    'seat_count'      => $schedule['seat_count'],
                    'batch'           => !empty($batches) ? implode(',', $batches) : null,
                    'semester'        => !empty($semesters) ? implode(',', $semesters) : null,
                    'credit_points'   => $schedule['credit_points'],
                ];

                if (!empty($schedule['schedule_id'])) {
                    EventSchedule::where('id', $schedule['schedule_id'])
                        ->where('event_id', $event->id)
                        ->update($data);
                } else {
                    EventSchedule::create($data);
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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'event' => $event,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Failed to save event: ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save event',
                'error' => 'Failed to save event',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $event = Event::with('schedules.registrations')->findOrFail($id);
        foreach ($event->schedules as $schedule) {
            if ($schedule->registrations->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete. Students already registered.'
                ]);
            }
        }
        $delete_schedule = EventSchedule::where('event_id', $id)->delete();
        $event->delete();
        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully'
        ]);
    }

    public function publishEvent(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id'
        ]);

        $event = Event::where('id', $request->event_id)
            ->update([
                'publish' => 1
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Event published successfully'
        ]);
    }
}
