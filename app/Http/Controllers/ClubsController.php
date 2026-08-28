<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Club;
use App\Models\Faculty;
use App\Helpers\ActivityLog;
use Illuminate\Http\Request;

class ClubsController extends Controller
{
    public function index(Request $request)
    {
        $query = Club::with('get_faculty');
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }
        if ($request->filled('faculty_id')) {
            $query->where('faculty_id', $request->faculty_id);
        }
        $this->data['clubs'] = $query
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();
        $this->data['faculties'] = Faculty::orderBy('name')->get();
        return view('admin/club_list')->with($this->data);
    }

    public function createClub(Request $request)
    {
        $this->data['faculty'] = Faculty::all();
        if ($request->club_id) {
            $clubId = decrypt($request->club_id);
            $this->data['edit_club'] = Club::where('id', $clubId)->first();
        }
        return view('admin/create_club')->with($this->data);
    }

    public function saveClub(Request $request)
    {

        $rules = [
            'club_name'   => 'required',
            'faculty_id'   => 'required',
            'description'   => 'nullable',
        ];

        $request->validate($rules);
        try {
            if (!empty($request['club_id'])) {
                $message = 'Club Updated successfully';
                $club = Club::find($request['club_id']);
            } else {
                $club = new Club();
                $message = 'Club saved successfully';
            }

            $club->name  = $request['club_name'];
            $club->faculty_id = $request['faculty_id'] ?? '';
            $club->description = $request['description'] ?? '';
            $club->save();

            if (!empty($request['club_id'])) {
                ActivityLog::add($club->name . ' - Club Updated', auth('admin')->user());
            } else {
                ActivityLog::add($club->name . ' - New Club Created', auth('admin')->user());
            }
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save Club',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
