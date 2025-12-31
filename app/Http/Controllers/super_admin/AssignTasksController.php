<?php

namespace App\Http\Controllers\super_admin;

use App\Helpers\ActivityLog;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Event;
use App\Models\Faculty;
use App\Models\TaskImage;
use App\Models\TaskImages;
use App\Models\Tasks;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AssignTasksController extends Controller
{
   public function index(Request $request)
   {

    if($request->ajax() && $request->task_status_change){
             $update = Tasks::where('id',$request->id)->update(['status' => 'accepted']);
    }

    $now = Carbon::now();
    $adminId = Auth::guard('admin')->id();
    $this->data['tasks'] = Tasks::where('created_by', $adminId)->with('get_admin', 'get_task_images')->get();
    $this->data['pending_tasks'] = Tasks::where(['created_by' => $adminId, 'status' => 'pending'])->count();
    $this->data['ongoing_tasks'] = Event::with('get_task')
            ->whereHas('get_task', function ($query) use ($adminId) {
                $query->where('created_by', $adminId);
                    // ->whereNotIn('status', ['pending', 'accepted']);
            })
            ->whereDate('event_date', $now->toDateString())
            // ->whereNotIn('status', ['completed'])
            ->count();
    $this->data['completed_tasks'] = Tasks::where(['created_by' => $adminId, 'status' => 'accepted'])->count();
    $this->data['admins'] = Admin::where('role_id', 2)->get();
    $this->data['event'] = Tasks::where('created_by', $adminId)->get();
    return view('super_admin.assign_task_index')->with($this->data);
   }

   public function createAssignTasks(Request $request)
   {
       if ($request->task_id) {
           $taskId = decrypt($request->task_id);
           $this->data['edit_task'] = Tasks::with('get_task_images')->where('id', $taskId)->first();
        }
        $this->data['admins'] = Admin::where('role_id',2)->get();
    return view('super_admin.create_assign_task')->with($this->data);
   }

    public function saveTasks(Request $request)
    {
        try {
            // ---------- VALIDATION ----------
            $rules = [
                'admin_id'      => 'required',
                'task_title'    => 'required|string',
                'description'   => 'required|string',
                'priority'      => 'required',
                'deadline_date' => 'required|date',
            ];
            // Multiple proof images validation
            if ($request->hasFile('proof')) {
                $rules['proof.*'] = 'image|mimes:jpeg,png,jpg|max:4096';
            }
            $request->validate($rules);
            // ---------- CREATE OR UPDATE ----------
            if (!empty($request->task_id)) {
                $tasks = Tasks::findOrFail($request->task_id);
                $message = "Task Updated Successfully";
            } else {
                $tasks = new Tasks();
                $message = "Task Created Successfully";
            }

            // ---------- SAVE BASIC DETAILS ----------
            $tasks->admin_id      = $request->admin_id;
            $tasks->created_by    = Auth::guard('admin')->id();
            $tasks->title         = $request->task_title;
            $tasks->description   = $request->description;
            $tasks->priority      = $request->priority;
            $tasks->deadline_date = $request->deadline_date;
            $tasks->save();
            if(!empty($request->task_id)){
                ActivityLog::add($tasks->title . ' - Task Updated', auth('admin')->user());
            }else{
                ActivityLog::add($tasks->title .' - New Task Created ', auth('admin')->user());
            }
            // ---------- MULTIPLE IMAGE UPLOAD ----------
            if(isset($request->removed_images) && !empty($request->removed_images)){
                $ids = json_decode($request->removed_images);
                foreach ($ids as $id) {
                    $img = TaskImage::find($id);
                    if ($img) {
                         Storage::disk('public')->delete($img->file_path);
                        $img->delete();
                    }
                }
            }

            if ($request->hasFile('proof')) {
                foreach ($request->file('proof') as $file) {
                    $imageName = time() . '_' . uniqid() . '.' . $file->getClientOriginalName();
                    $path = $file->storeAs('task_images', $imageName, 'public');
                    $exists = TaskImage::where(['task_id' => $tasks->id, 'file_name' => $imageName, 'file_path' => $path])->first();
                    if(!$exists){
                        $taskimage = new TaskImage();
                        $taskimage->task_id =  $tasks->id;
                        $taskimage->file_name = $imageName;
                        $taskimage->file_path = $path;
                        $taskimage->file_type = $file->getClientOriginalExtension();
                        $taskimage->save();
                    }
                }
            }
            // ---------- SUCCESS RESPONSE ----------
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function viewTask(Request $request){
        $taskId = decrypt($request->task_id);
        $this->data['task'] = Tasks::where('id', $taskId)->first();
         return view('admin.admin_task_view')->with($this->data);
    }
}
