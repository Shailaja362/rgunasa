<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLog;
use App\Models\Batch;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BatchController extends Controller
{
    public function index()
    {
        $this->data['batches'] = Batch::orderBy('name')->paginate(10);
        return view('admin/batch_list')->with($this->data);
    }

    public function createBatch(Request $request)
    {
        if ($request->batch_id) {
            $batchId = decrypt($request->batch_id);
            $this->data['edit_batch'] = Batch::where('id', $batchId)->first();
        }
        return view('admin/create_batch')->with($this->data);
    }

    public function saveBatch(Request $request)
    {
        $rules = [
            'batch_name' => [
                'required',
                'regex:/^\d{4}-\d{4}$/',
                Rule::unique('batches', 'name')->ignore($request->batch_id),
            ],
        ];

        $request->validate($rules, [
            'batch_name.regex' => 'Batch must be in YYYY-YYYY format',
        ]);

        [$start, $end] = array_map('intval', explode('-', $request->batch_name));
        if ($end <= $start) {
            return response()->json([
                'success' => false,
                'message' => 'Batch end year must be greater than start year',
            ], 422);
        }

        try {
            if (!empty($request['batch_id'])) {
                $message = 'Batch Updated successfully';
                $batch = Batch::find($request['batch_id']);
            } else {
                $batch = new Batch();
                $message = 'Batch saved successfully';
            }

            $batch->name = $request['batch_name'];
            $batch->save();

            if (!empty($request['batch_id'])) {
                ActivityLog::add($batch->name . ' - Batch Updated', auth('admin')->user());
            } else {
                ActivityLog::add($batch->name . ' - New Batch Created', auth('admin')->user());
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save Batch',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
