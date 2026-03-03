<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLog;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Faculty;
use Exception;
use Illuminate\Http\Request;

class FacultyController extends Controller
{
    public function index(Request $request)
    {
        $query = Faculty::with('get_department', 'get_designation');
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('mobile_number', 'LIKE', "%{$search}%")
                    ->orWhere('faculty_code', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('designation_id')) {
            $query->where('designation_id', $request->designation_id);
        }

        $this->data['faculty']  = $query
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();
        $this->data['departments'] = Department::orderBy('name')->get();
        $this->data['designations'] = Designation::orderBy('name')->get();
        return view('admin/faculty_list')->with($this->data);
    }

    public function createFaculty(Request $request)
    {
        $this->data['department'] = Department::all();
        $this->data['designation'] = Designation::all();
        if ($request->faculty_id) {
            $facultyId = decrypt($request->faculty_id);
            $this->data['edit_faculty'] = Faculty::where('id', $facultyId)->first();
        }
        return view('admin/create_faculty')->with($this->data);
    }

    public function saveFaculty(Request $request)
    {
        try {
            $rules = [
                'faculty_name'   => 'required',
                'email'          => 'required|email',
                'faculty_code'   => 'required',
                'mobile_number'  => 'required|digits:10',
                'department_id'  => 'required',
                'designation_id' => 'required',
            ];

            if (!empty($request['faculty_id'])){
                $exists = Faculty::where('email', $request['email'])->first();
                $mobile_exists = Faculty::where('mobile_number', $request['mobile_number'])->first();
                $faculty_exists = Faculty::where('faculty_code', $request['faculty_code'])->first();
                if ($exists && $exists->id != $request['faculty_id']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Email ID is already exists!',
                        'error' => 'Email ID is already exists!',
                    ], 500);
                }
                if ($mobile_exists  &&  $mobile_exists->id != $request['faculty_id']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Mobile Number is already exists!',
                        'error' => 'Mobile Number is already exists!',
                    ], 500);
                }
                if ($faculty_exists  &&  $faculty_exists->id != $request['faculty_id']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Faculty Code is already exists!',
                        'error' => 'Faculty Code is already exists!',
                    ], 500);
                }
            }else{
                $exists = Faculty::where('email', $request['email'])->exists();
                $mobile_exists = Faculty::where('mobile_number', $request['mobile_number'])->exists();
                $faculty_exists = Faculty::where('faculty_code', $request['faculty_code'])->exists();
                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Email ID is already exists!',
                        'error' => 'Email ID is already exists!',
                    ], 500);
                }
                if ($mobile_exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Mobile Number is already exists!',
                        'error' => 'Mobile Number is already exists!',
                    ], 500);
                }
                if ($faculty_exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Faculty Code is already exists!',
                        'error' => 'Faculty Code is already exists!',
                    ], 500);
                }
            }

            if (empty($request['faculty_id']) && !$request->has('old_banner')) {
                $rules['banner_image'] = 'required|image|mimes:jpeg,png,jpg';
            } else if ($request->hasFile('banner_image')) {
                $rules['banner_image'] = 'image|mimes:jpeg,png,jpg';
            }

            $request->validate($rules);
            if (!empty($request['faculty_id'])) {
                $message = 'Faculty Updated successfully';
                $faculty = Faculty::find($request['faculty_id']);
            } else {
                $faculty = new Faculty();
                $message = 'Faculty saved successfully';
            }

            if ($request->hasFile('banner_image')) {
                $file = $request->file('banner_image');
                $img_name = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('faculty', $img_name, 'public');
                $faculty->profile_pic = 'faculty/' . $img_name;
            } elseif ($request->has('old_banner')) {
                $faculty->profile_pic = $request->old_banner;
            }

            $faculty->name  = $request['faculty_name'];
            $faculty->email  = $request['email'];
            $faculty->mobile_number  = $request['mobile_number'];
            $faculty->faculty_code = $request['faculty_code'] ?? '';
            $faculty->department_id = $request['department_id'] ?? '';
            $faculty->designation_id = $request['designation_id'] ?? '';
            $faculty->save();

            if (!empty($request['faculty_id'])) {
                ActivityLog::add($faculty->name . ' - Faculty Updated', auth('admin')->user());
            } else {
                ActivityLog::add($faculty->name . ' - New Faculty Created', auth('admin')->user());
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
