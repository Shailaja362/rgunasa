<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PolicyController extends Controller
{
    public function privacyPolicy()
    {
        return response()->json([
            'data'    => view('policies.privacy_policy_content')->render(),
            'status'  => 200,
            'message' => 'Privacy Policy Fetched Successfully',
        ]);
    }

    public function termsConditions()
    {
        return response()->json([
            'data'    => view('policies.terms_conditions_content')->render(),
            'status'  => 200,
            'message' => 'Terms & Conditions Fetched Successfully',
        ]);
    }

    public function saveToken(Request $request)
    {
        try {

            $request->validate([
                'device_token' => 'required',
            ]);

            $student = Auth::guard('student-api')->id();
            $list = Student::find($student);
            $list->update([
                'device_token' => $request->device_token
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Device token saved successfully',
            ]);

        } catch (\Throwable $th) {

            return response()->json([
                'status' => $th->getCode() ?: 500,
                'message' => $th->getMessage(),
            ], $th->getCode() ?: 500);
        }
    }
}
