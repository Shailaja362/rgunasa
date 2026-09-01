<?php

namespace App\Http\Controllers\Auth;

use App\Models\Student;
use App\Helpers\ActivityLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class StudentAuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!$validator->passes()) {
            return redirect()->route('student.login')
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        if (!Auth::guard('student')->attempt($credentials, $request->get('remember'))) {
            return redirect()->route('student.login')
                ->withErrors(['password' => 'Either Email/Password is incorrect'])
                ->withInput($request->only('email'));
        }
        session()->forget('admin');
        session()->forget('super_admin');
        $student = Auth::guard('student')->id();
        $studentdetail = Student::with('get_department')->where('id', $student)->first();
        session()->put('student', $studentdetail);
        ActivityLog::add($studentdetail->name . " - Student Login", auth('student')->user());
        return redirect()->route('student_dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('student')->logout();
        Auth::guard('admin')->logout();
        session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('student.login');
    }
}
