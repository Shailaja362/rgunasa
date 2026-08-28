<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLog;
use App\Models\Batch;
use App\Models\EventSchedule;
use App\Models\Student;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BatchController extends Controller
{
    public function index(Request $request)
    {
        $query = Batch::query();
        if ($request->filled('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%");
        }
        $this->data['batches'] = $query
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
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

        DB::beginTransaction();
        try {
            if (!empty($request['batch_id'])) {
                $message = 'Batch Updated successfully';
                $batch = Batch::find($request['batch_id']);
                $oldName = $batch->name;
            } else {
                $batch = new Batch();
                $message = 'Batch saved successfully';
                $oldName = null;
            }

            $batch->name = $request['batch_name'];
            $batch->save();

            if ($oldName !== null && $oldName !== $batch->name) {
                $this->renameBatchEverywhere($oldName, $batch->name);
            }

            if (!empty($request['batch_id'])) {
                ActivityLog::add($batch->name . ' - Batch Updated', auth('admin')->user());
            } else {
                ActivityLog::add($batch->name . ' - New Batch Created', auth('admin')->user());
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save Batch',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * students.batch and event_schedules.batch store the batch name as a
     * string rather than a foreign key (event_schedules.batch is even a
     * comma-separated list), so renaming a batch here has to be propagated
     * to every row that copied the old name.
     */
    private function renameBatchEverywhere($oldName, $newName)
    {
        Student::where('batch', $oldName)->update(['batch' => $newName]);

        EventSchedule::where('batch', 'like', "%{$oldName}%")
            ->get()
            ->each(function ($schedule) use ($oldName, $newName) {
                $values = array_map('trim', explode(',', $schedule->batch));
                $values = array_map(fn($v) => $v === $oldName ? $newName : $v, $values);
                $schedule->batch = implode(',', array_unique($values));
                $schedule->save();
            });
    }

    /**
     * True if this batch name is still referenced by any student or event
     * schedule, since those tables copy the name as a plain string rather
     * than a foreign key.
     */
    private function isBatchInUse($name)
    {
        return Student::where('batch', $name)->exists()
            || EventSchedule::whereRaw('FIND_IN_SET(?, batch)', [$name])->exists();
    }

    public function destroy($id)
    {
        $batch = Batch::findOrFail($id);

        if ($this->isBatchInUse($batch->name)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete. This batch is already assigned to students or event schedules.',
            ], 422);
        }

        $batch->delete();

        ActivityLog::add($batch->name . ' - Batch Deleted', auth('admin')->user());

        return response()->json([
            'success' => true,
            'message' => 'Batch deleted successfully',
        ]);
    }
}
