<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLog;
use App\Models\Admin;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Role;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Admin::with('get_role', 'get_department', 'get_designation');
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('mobile_number', 'LIKE', "%{$search}%")
                    ->orWhere('emp_code', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('designation_id')) {
            $query->where('designation_id', $request->designation_id);
        }

        $this->data['admins'] = $query
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();
        $this->data['departments'] = Department::orderBy('name')->get();
        $this->data['designations'] = Designation::orderBy('name')->get();
        return view('super_admin/admin_list')->with($this->data);
    }

    public function createAdmin(Request $request)
    {
        $id = [1, 2];
        $this->data['roles'] = Role::whereIn('id', $id)->get();
        if ($request->admin_id) {
            $adminId = decrypt($request->admin_id);
            $this->data['edit_admin'] = Admin::where('id', $adminId)->first();
        }
        $this->data['department'] = Department::get();
        $this->data['designation'] = Designation::get();
        return view('super_admin/create_admin')->with($this->data);
    }

    public function saveAdmin(Request $request)
    {
        try {
            $rules = [
                'admin_name'  => 'required',
                'email'         => 'required|email',
                'emp_code' => 'nullable',
                'mobile_number' => 'required|digits:10',
                'role_id' => 'required',
                'department_id' => 'required',
                'designation_id' => 'required'
            ];

            if ($request['role_id'] == 1) {
                $rules['security_code'] =  'required';
            }

            if (!empty($request['admin_id'])) {
                $exists = Admin::where('email', $request['email'])->first();
                $mobile_exists = Admin::where('mobile_number', $request['mobile_number'])->first();
                if ($exists && $exists->id != $request['admin_id']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Email ID is already exists!',
                        'error' => 'Email ID is already exists!',
                    ], 500);
                }
                if ($mobile_exists  &&  $mobile_exists->id != $request['admin_id']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Mobile Number is already exists!',
                        'error' => 'Mobile Number is already exists!',
                    ], 500);
                }
            } else {
                $exists = Admin::where('email', $request['email'])->exists();
                $mobile_exists = Admin::where('mobile_number', $request['mobile_number'])->exists();
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
            }

            if (empty($request['admin_id']) && !$request->has('old_banner')) {
                $rules['banner_image'] = 'required|image|mimes:jpeg,png,jpg|max:2048';
            } else if ($request->hasFile('banner_image')) {
                $rules['banner_image'] = 'image|mimes:jpeg,png,jpg|max:2048';
            }

            $request->validate($rules);

            if (!empty($request['admin_id'])) {
                $message = 'Admin Updated successfully';
                $admin = Admin::find($request['admin_id']);
            } else {
                $admin = new Admin();
                $message = 'Admin saved successfully';
            }

            if ($request->hasFile('banner_image')) {
                $file = $request->file('banner_image');
                $img_name = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('admin', $img_name, 'public');
                $admin->profile_pic = 'admin/' . $img_name;
            } elseif ($request->has('old_banner')) {
                $admin->profile_pic = $request->old_banner;
            }

            $password = Hash::make($request['mobile_number']);
            $admin->name  = $request['admin_name'];
            $admin->email  = $request['email'];
            $admin->password  = $password;
            $admin->mobile_number  = $request['mobile_number'];
            $admin->role_id = $request['role_id'] ?? '';
            $admin->security_code = $request['security_code'] ?? '';
            $admin->emp_code = $request['emp_code'] ?? '';
            $admin->department_id = $request['department_id'] ?? '';
            $admin->designation_id = $request['designation_id'] ?? '';
            $admin->save();

            if (!empty($request['admin_id'])) {
                ActivityLog::add($admin->name . ' - Admin Detail Updated', auth('admin')->user());
            } else {
                ActivityLog::add($admin->name . ' - New Admin Created', auth('admin')->user());
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
