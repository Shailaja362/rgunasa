<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLog;
use App\Models\Department;
use App\Support\RelatedRecordChecker;
use Exception;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Department::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }
        $this->data['departments'] = $query
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();
        return view('admin/department_list')->with($this->data);
    }

    public function createDepartment(Request $request)
    {
        if ($request->department_id) {
            $departmentId = decrypt($request->department_id);
            $this->data['edit_department'] = Department::where('id', $departmentId)->first();

        }
        return view('admin/create_department')->with($this->data);
    }

    public function saveDepartment(Request $request)
    {

        $rules = [
            'department_name'   => 'required',
            'department_code'   => 'required',
        ];

        $request->validate($rules);
        try {
            if (!empty($request['department_id'])) {
                $message = 'Department Updated successfully';
                $department = Department::find($request['department_id']);
            } else {
                $department = new Department();
                $message = 'Department saved successfully';
            }

            $department->name  = $request['department_name'];
            $department->code = $request['department_code'] ?? '';
            $department->save();

            if (!empty($request['department_id'])) {
                ActivityLog::add($department->name . ' - Department Updated', auth('admin')->user());
            } else {
                ActivityLog::add($department->name . ' - New Department Created', auth('admin')->user());
            }
            
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save Department',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id, Request $request)
    {
        $department = Department::findOrFail($id);

        $counts = RelatedRecordChecker::counts([
            ['table' => 'programmes', 'column' => 'department_id', 'value' => $department->id, 'label' => 'programme(s)'],
            ['table' => 'faculties', 'column' => 'department_id', 'value' => $department->id, 'label' => 'faculty member(s)'],
            ['table' => 'students', 'column' => 'department_id', 'value' => $department->id, 'label' => 'student(s)'],
            ['table' => 'admins', 'column' => 'department_id', 'value' => $department->id, 'label' => 'admin(s)'],
        ]);

        if (!empty($counts)) {
            return response()->json([
                'success' => false,
                'blocking' => true,
                'message' => RelatedRecordChecker::blockingMessage('department', $counts),
            ], 422);
        }

        if (!$request->boolean('confirmed')) {
            return response()->json([
                'success' => false,
                'blocking' => false,
                'message' => RelatedRecordChecker::confirmMessage('department', []),
            ]);
        }

        $department->delete();

        ActivityLog::add($department->name . ' - Department Deleted', auth('admin')->user());

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully',
        ]);
    }
}
