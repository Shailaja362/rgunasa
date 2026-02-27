<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\CreditPoint;
use App\Models\Programme;
use Illuminate\Http\Request;

class CreditPointAssignController extends Controller
{
    public function index(Request $request)
    {
        $this->data['credit_points'] = CreditPoint::paginate(10);
        $editData = null;

        if ($request->edit_id) {
            $editData = CreditPoint::findOrFail($request->edit_id);
        }
        $this->data['editData'] = $editData;
        return view('admin.credit_point_index')->with($this->data);
    }

    public function saveCreditPoint(Request $request)
    {
        if ($request->id) {
            $credit = CreditPoint::where('id', $request->id)->update([
                'credit_points' => $request->credit_points
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Credit Points Updated Successfully'
            ]);
        } else {
            $request->validate([
                'semester' => 'required',
                'credit_points' => 'required|numeric|min:0'
            ]);
            $existing = CreditPoint::where('semester', $request->semester)
                ->first();
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credit Points for this Semester already exists'
                ]);
            }

            $creditpoint = new CreditPoint();
            $creditpoint->semester = $request->semester;
            $creditpoint->credit_points = $request->credit_points;
            $creditpoint->save();

            return response()->json([
                'success' => true,
                'message' => 'Credit Points Created Successfully'
            ]);
        }
    }
}
