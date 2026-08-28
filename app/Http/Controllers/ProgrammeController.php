<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Programme;
use App\Models\Department;
use App\Helpers\ActivityLog;
use Illuminate\Http\Request;

class ProgrammeController extends Controller
{
    public function index(Request $request)
    {
        $query = Programme::with('get_department');
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        $this->data['programmes'] = $query
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();
        $this->data['departments'] = Department::orderBy('name')->get();
        return view('admin/programme_list')->with($this->data);
    }

    public function createProgramme(Request $request)
    {
        $this->data['department'] = Department::all();
        if ($request->programme_id) {
            $programmeId = decrypt($request->programme_id);
            $this->data['edit_programme'] = Programme::where('id', $programmeId)->first();
        }
        return view('admin/create_programme')->with($this->data);
    }

    public function saveProgramme(Request $request)
    {

        $rules = [
            'programme_name'   => 'required',
            'programme_code'   => 'required',
            'department_id'   => 'required',
            'graduate_type'   => 'required',
        ];

        $request->validate($rules);
        try {
            if (!empty($request['programme_id'])) {
                $message = 'Programme Updated successfully';
                $programme = Programme::find($request['programme_id']);
            } else {
                $programme = new Programme();
                $message = 'Programme saved successfully';
            }

            $programme->name  = $request['programme_name'];
            $programme->code = $request['programme_code'] ?? '';
            $programme->department_id = $request['department_id'] ?? '';
            $programme->graduate_type = $request['graduate_type'] ?? '';
            $programme->save();

            if (!empty($request['programme_id'])) {
                ActivityLog::add($programme->name . ' - Programme Updated', auth('admin')->user());
            } else {
                ActivityLog::add($programme->name . ' - New Programme Created', auth('admin')->user());
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save Programme',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
