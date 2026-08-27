<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Batch;
use App\Models\Student;
use App\Models\Programme;
use App\Models\Department;
use App\Helpers\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with('get_department', 'get_programme');
        //  Text search
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('mobile_number', 'LIKE', "%{$search}%")
                    ->orWhere('register_number', 'LIKE', "%{$search}%")
                    ->orWhere('batch', 'LIKE', "%{$search}%");
            });
        }
        // Department filter
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        $this->data['student'] = $query
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();
        $this->data['departments'] = Department::orderBy('name')->get();
        $this->data['batch'] = Student::whereNotNull('batch')
            ->distinct()
            ->orderBy('batch')
            ->pluck('batch');
        return view('admin.student_list')->with($this->data);
    }

    public function createStudent(Request $request)
    {
        $this->data['department'] = Department::all();
        $this->data['programme'] = Programme::all();
        $this->data['batches'] = Batch::orderBy('name')->pluck('name');
        if ($request->student_id) {
            $studentId = decrypt($request->student_id);
            $this->data['edit_student'] = Student::where('id', $studentId)->first();
        } else {
            $this->data['edit_student'] = null;
        }
        return view('admin/create_student')->with($this->data);
    }

    public function saveStudent(Request $request)
    {
        try {
            $rules = [
                'student_name'  => 'required',
                'email'         => 'required|email',
                'date_of_birth' => 'required',
                'mobile_number' => 'required|digits:10',
                'department_id' => 'required',
                'programme_id'  => 'required',
                'gender' => 'required',
                'section' => 'required',
                'register_number' => 'required',
                'batch' => [
                    'required',
                    'regex:/^\d{4}-\d{4}$/',
                    function ($attribute, $value, $fail) {
                        [$start, $end] = explode('-', $value);
                        if ($end <= $start) {
                            $fail('Batch end year must be greater than start year.');
                        }
                        if (($end - $start) > 10) {
                            $fail('Batch range is invalid.');
                        }
                    },
                ],
                'semester' => 'required'
            ];

            if (!empty($request['student_id'])) {
                $exists = Student::where('email', $request['email'])->first();
                $mobile_exists = Student::where('mobile_number', $request['mobile_number'])->first();
                if ($exists && $exists->id != $request['student_id']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Email ID is already exists!',
                        'error' => 'Email ID is already exists!',
                    ], 500);
                }
                if ($mobile_exists  &&  $mobile_exists->id != $request['student_id']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Mobile Number is already exists!',
                        'error' => 'Mobile Number is already exists!',
                    ], 500);
                }
            } else {
                $exists = Student::where('email', $request['email'])->exists();
                $mobile_exists = Student::where('mobile_number', $request['mobile_number'])->exists();
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

            if (empty($request['student_id']) && !$request->has('old_banner')) {
                $rules['banner_image'] = 'required|image|mimes:jpeg,png,jpg|max:2048';
            } else if ($request->hasFile('banner_image')) {
                $rules['banner_image'] = 'image|mimes:jpeg,png,jpg|max:2048';
            }

            $request->validate($rules);
            if (!empty($request['student_id'])) {
                $message = 'Student Updated successfully';
                $student = Student::find($request['student_id']);
            } else {
                $student = new Student();
                $message = 'Student saved successfully';
            }

            if ($request->hasFile('banner_image')) {
                $file = $request->file('banner_image');
                $img_name = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('student', $img_name, 'public');
                $student->profile_pic = 'student/' . $img_name;
            } elseif ($request->has('old_banner')) {
                $student->profile_pic = $request->old_banner;
            }
            $password = Hash::make($request['mobile_number']);
            $student->name  = $request['student_name'];
            $student->email  = $request['email'];
            $student->password  = $password;
            $student->mobile_number  = $request['mobile_number'];
            $student->date_of_birth = $request['date_of_birth'] ?? '';
            $student->department_id = $request['department_id'] ?? '';
            $student->programme_id = $request['programme_id'] ?? '';
            $student->gender = $request['gender'] ?? '';
            $student->section = $request['section'] ?? '';
            $student->register_number = $request['register_number'] ?? '';
            $student->semester = $request['semester'] ?? '';
            $student->batch = $request['batch'] ?? '';
            $student->save();

            if (!empty($request['student_id'])) {
                ActivityLog::add($student->name . ' - Student Updated', auth('admin')->user());
            } else {
                ActivityLog::add($student->name . ' - New Student Created', auth('admin')->user());
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

    public function registerStudent(Request $request)
    {
        $this->data['department'] = Department::all();
        $this->data['programme'] = Programme::all();
        $this->data['batches'] = Batch::orderBy('name')->pluck('name');
        if ($request->student_id) {
            $studentId = decrypt($request->student_id);
            $this->data['edit_student'] = Student::where('id', $studentId)->first();
        }
        return view('student/register_student')->with($this->data);
    }

    public function registerSave(Request $request)
    {
        try {
            $rules = [
                'student_name'  => 'required',
                'email'         => 'required|email',
                'date_of_birth' => 'required',
                'mobile_number' => 'required|digits:10',
                'department_id' => 'required',
                'programme_id'  => 'required',
                'gender' => 'required',
                'section' => 'required',
                'batch' => [
                    'required',
                    'regex:/^\d{4}-\d{4}$/',
                    function ($attribute, $value, $fail) {

                        [$start, $end] = explode('-', $value);

                        if ($end <= $start) {
                            $fail('Batch end year must be greater than start year.');
                        }

                        if (($end - $start) > 10) {
                            $fail('Batch range is invalid.');
                        }
                    },
                ],
                'semester' => 'required'
            ];

            $exists = Student::where('email', $request['email'])->exists();
            $mobile_exists = Student::where('mobile_number', $request['mobile_number'])->exists();
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


            if ($request->hasFile('banner_image')) {
                $rules['banner_image'] = 'image|mimes:jpeg,png,jpg|max:2048';
            }

            $request->validate($rules);

            $student = new Student();
            $message = 'Student Registered Successfully';

            if ($request->hasFile('banner_image')) {
                $file = $request->file('banner_image');
                $img_name = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('student', $img_name, 'public');
                $student->profile_pic = 'student/' . $img_name;
            }

            $password = Hash::make($request['mobile_number']);
            $student->name  = $request['student_name'];
            $student->email  = $request['email'];
            $student->password  = $password;
            $student->mobile_number  = $request['mobile_number'];
            $student->date_of_birth = $request['date_of_birth'] ?? '';
            $student->department_id = $request['department_id'] ?? '';
            $student->programme_id = $request['programme_id'] ?? '';
            $student->gender = $request['gender'] ?? '';
            $student->section = $request['section'] ?? '';
            $student->register_number = $request['register_number'] ?? '';
            $student->semester = $request['semester'] ?? '';
            $student->batch = $request['batch'] ?? '';
            $student->save();

            session()->put('register_student', $student);

            ActivityLog::add($student->name . ' - New Student Created', $student);

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

    public function promoteStudent(Request $request)
    {
        $request->validate([
            'batch' => 'required'
        ]);

        $students = Student::where('batch', $request->batch)
            ->where('semester', '<', 8)
            ->get();
        if ($students->isEmpty()) {
            return back()->with('error', 'All students are already in final semester.');
        }
        Student::where('batch', $request->batch)
            ->where('semester', '<', 8)
            ->increment('semester');
        return back()->with('success', 'Eligible students promoted successfully.');
    }
}
