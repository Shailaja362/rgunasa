<?php

namespace App\Http\Controllers\API;

use App\Helpers\ActivityLog;
use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class StudentAuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 409,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 409);
        }

        $credentials = [
            'email'    => $request->email,
            'password' => $request->password,
        ];
        try {
            if (!auth('student-api')->attempt($credentials)) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Invalid email or password',
                ], 400);
            }
            $student = auth('student-api')->user();
            ActivityLog::add(
                $student->name . ' - Student API Login',
                $student
            );
            $oneYearInMinutes = 60 * 24 * 365;
            $token = auth('student-api')
                ->setTTL($oneYearInMinutes)
                ->login($student);
            return response()->json([
                'status' => 200,
                'message' => 'Login successful',
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'mobile_number' => $student->mobile_number,
                    'access_token' => $token,
                    'expires_in'   => $oneYearInMinutes * 60, // seconds
                ]
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 409,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 409);
        }

        $student = Student::where('email', $request->email)->first();

        if (!$student) {
            return response()->json([
                'status'  => 400,
                'message' => 'Invalid email',
            ], 400);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Eail Verified Successfully!'
        ], 200);
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'email' => 'required|email|exists:students,email',
                'new_password' => 'required|min:6',
                'confirm_password' => 'required|same:new_password',
            ],
            [
                'confirm_password.same' => 'Password and confirm password do not match.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status'  => 409,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 409);
        }

        $student = Student::where('email', $request->email)->first();
        if (!$student) {
            return response()->json([
                'status'  => 400,
                'message' => 'Invalid request',
            ], 400);
        }

        $student->password = Hash::make($request->new_password);
        $student->save();

        return response()->json([
            'status' => 200,
            'message' => 'Password updated successfully!'
        ], 200);
    }
}
