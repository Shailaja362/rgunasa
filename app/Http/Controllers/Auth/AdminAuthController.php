<?php

namespace App\Http\Controllers\Auth;

use App\Models\Admin;
use App\Helpers\ActivityLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminAuthController extends Controller
{
    public function index(Request $request)
    {
        $this->data['credential_value'] = $request->all();
        $prefix = request()->route()->getPrefix();
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!$validator->passes()) {
            return redirect()->route('admin.login')
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        if (!Auth::guard('admin')->attempt($credentials, $request->get('remember'))) {
            return redirect()->route('admin.login')
                ->withErrors(['password' => 'Either Email/Password is incorrect'])
                ->withInput($request->only('email'));
        }

        $admin = Auth::guard('admin')->id();
        $admin_details = Admin::with('get_department', 'get_designation')->where('id', $admin)->first();


        if($admin_details->role_id == 1){
            session()->put('super_admin', $admin_details);
            session()->forget('admin');
            session()->forget('student');
        }else{
            session()->put('admin', $admin_details);
            session()->forget('student');
            session()->forget('super_admin');
        }

        if (!empty(session()->get('super_admin'))) {
            ActivityLog::add($admin_details->name . " - Super Admin Login ", auth('admin')->user());
        } else if (!empty(session()->get('admin'))) {
            ActivityLog::add($admin_details->name . " - Admin Login ", auth('admin')->user());
        }

        return view('admin.role_check_login')->with($this->data);
    }

    public function logout(Request $request)
    {
        Auth::guard('student')->logout();
        Auth::guard('admin')->logout();
        session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
