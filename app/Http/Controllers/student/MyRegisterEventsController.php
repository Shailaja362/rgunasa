<?php

namespace App\Http\Controllers\student;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\StudentEventRegistration;
use App\Models\StudentFeedback;
use App\Models\StudentUploadProof;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MyRegisterEventsController extends Controller
{
    public function index(Request $request)
    {
        $student = session()->get('student');
        $now = Carbon::now();
        $this->data['registeredEvents'] = StudentEventRegistration::with('event')->where('student_id', $student->id)
            ->get();
        $this->data['completedEvents'] = StudentEventRegistration::with('event', 'get_event_attendance')
            ->whereHas('get_event_attendance', function ($query) use ($now) {
                $query->whereNotNull('entry_time')
                    ->whereNotNull('exit_time');
            })
            ->where('status', 3)
            ->where('student_id', $student->id)
            ->get();

        $this->data['activeCount'] = StudentEventRegistration::where('student_id', $student->id)
            ->where('status', 1)
            ->count();

        $this->data['attendedCount'] = StudentEventRegistration::with('get_event_attendance')->where('student_id', $student->id)
            ->whereHas('get_event_attendance', function ($query) use ($now) {
                $query->whereNotNull('entry_time')
                    ->whereNotNull('exit_time');
            })
            ->where('status', 3)
            ->count();
        $events = Event::get();
        $myuploads = StudentUploadProof::select('student_id', 'event_id')
            ->where('student_id', $student->id)
            ->groupBy('student_id', 'event_id')
            ->get();
        $activecount = StudentEventRegistration::where('student_id', $student->id)->count();
        $this->data['pending_uploads'] =  $activecount - count($myuploads);
        return view('student.my_register_event')->with($this->data);
    }

    public function uploadProof(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|integer',
            'event_id'   => 'required|integer',

            // THIS IS THE FIX
            'proof'      => 'nullable|array',
            'proof.*'    => 'image|mimes:jpg,jpeg,png|max:10240',

            'ratings.*'  => 'required|integer|min:1|max:5',
            'comments'   => 'nullable|string|max:500',
        ]);


        try {
            $hasExistingProof = StudentUploadProof::where([
                'student_id' => $request->student_id,
                'event_id'   => $request->event_id,
            ])->exists();

            if (!$hasExistingProof && !$request->hasFile('proof')) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one proof image is required.'
                ], 422);
            }
            if ($request->hasFile('proof')) {
                foreach ($request->file('proof') as $file) {
                    $fileName = $file->getClientOriginalName();
                    $filePath = $file->storeAs('student_upload_proof', $fileName, 'public');
                    $exists = StudentUploadProof::where(['student_id' => $validated['student_id'], 'event_id' => $validated['event_id'], 'file_name' =>  $fileName, 'file_path' => $filePath])->first();
                    if (!$exists) {
                        $upload = new StudentUploadProof();
                        $upload->student_id = $validated['student_id'];
                        $upload->event_id   = $validated['event_id'] ?? null;
                        $upload->file_name  = $fileName; // Original filename
                        $upload->file_path  = $filePath;         // Public path
                        $upload->file_type  = $file->getClientOriginalExtension();
                        $upload->save();
                    }
                }
            }

            $exists_feedback  = StudentFeedback::where(['student_id' => $validated['student_id'], 'event_id' => $validated['event_id']])->first();

            if (!$exists_feedback) {
                $feedback = new StudentFeedback();
                $feedback->student_id = $validated['student_id'];
                $feedback->event_id   = $validated['event_id'] ?? null;
                $feedback->ratings  = json_encode($request->ratings); // Original filename
                $feedback->comments  = $request->comments;         // Public path
                $feedback->save();
            }
            return response()->json([
                'success' => true,
                'message' => 'Proof uploaded successfully!',
            ]);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getUploadedProof(Request $request)
    {

        $proofs = StudentUploadProof::where([
            'student_id' => $request->student_id,
            'event_id'   => $request->event_id,
        ])->get();

        $feedback = StudentFeedback::where([
            'student_id' => $request->student_id,
            'event_id'   => $request->event_id,
        ])->first();

        return response()->json([
            'proofs'   => $proofs,
            'feedback' => $feedback
        ]);
    }
}
