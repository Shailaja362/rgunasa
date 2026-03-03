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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MyRegisterEventsController extends Controller
{
    public function index(Request $request)
    {
        $student = session()->get('student');
        $this->data['student'] =  $student;
        $now = Carbon::now();
        $this->data['registeredEvents'] = StudentEventRegistration::with('event', 'get_event_schedule', 'student')->where('student_id', $student->id)
        ->whereHas('get_event_schedule', function ($q) use ($student) {
            $q->where('programme_id', $student->programme_id)
                ->where('section', $student->section)
                ->where('batch', $student->batch)
                ->where('semester', $student->semester);
         })
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })
            ->get();
        $this->data['completedEvents'] = StudentEventRegistration::with('event', 'get_event_attendance', 'get_event_schedule', 'student')
            ->whereHas('get_event_attendance', function ($query) use ($student) {
                $query->whereNotNull('entry_time')
                       ->whereNotNull('exit_time')
                       ->where('student_id', $student->id);
            })
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })
            ->where('student_id', $student->id)
            ->get();

        $this->data['activeCount'] = StudentEventRegistration::with('event')->where('student_id', $student->id)
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })
            ->where('status', 1)
            ->count();
        $this->data['attendedCount'] = StudentEventRegistration::with('get_event_attendance','event')->where('student_id', $student->id)
            ->whereHas('get_event_attendance', function ($query) use ($student) {
                $query->whereNotNull('entry_time')
                    ->whereNotNull('exit_time')
                    ->where('student_id', $student->id);
            })
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })
            ->where('status', 3)
            ->count();
        $events = Event::where([
            'publish' => 1,
            'is_active' => 'y'
        ])->get();
        $myuploads = StudentUploadProof::with('event')->select('student_id', 'event_id')
            ->where('student_id', $student->id)
            ->whereHas('event', function ($query) {
              $query->where('publish', 1)
                    ->where('is_active', 'y');
            })
            ->groupBy('student_id', 'event_id')
            ->get();
        $activecount = StudentEventRegistration::with('event')
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })
            ->where('student_id', $student->id)->count();
        $this->data['pending_uploads'] =  $activecount - count($myuploads);
        return view('student.my_register_event')->with($this->data);
    }

    public function uploadProof(Request $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'student_id'      => 'required|integer',
                'event_id'        => 'required|integer',
                'schedule_id'     => 'required|integer',
                'proof.*'         => 'nullable|file|max:10240',
                'ratings'         => 'required|array',
                'ratings.*'       => 'required|integer|min:1|max:5',
                'comments'        => 'nullable|string|max:500',
                'removed_proofs'  => 'nullable|array',
            ]);

            /* ================= Delete removed proofs ================= */
            if (!empty($validated['removed_proofs'])) {
                $proofs = StudentUploadProof::whereIn('id', $validated['removed_proofs'])->get();

                foreach ($proofs as $proof) {
                    Storage::disk('public')->delete($proof->file_path);
                    $proof->delete();
                }
            }

            /* ================= Upload new files ================= */
            if ($request->hasFile('proof')) {
                foreach ($request->file('proof') as $file) {

                    $fileName = uniqid() . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('student_upload_proof', $fileName, 'public');

                    StudentUploadProof::create([
                        'student_id'        => $validated['student_id'],
                        'event_id'          => $validated['event_id'],
                        'event_schedule_id' => $validated['schedule_id'],
                        'file_name'         => $fileName,
                        'file_path'         => $filePath,
                        'file_type'         => $file->getClientOriginalExtension(),
                    ]);
                }
            }

            /* ================= Update feedback ================= */
            StudentFeedback::updateOrCreate(
                [
                    'student_id'        => $validated['student_id'],
                    'event_id'          => $validated['event_id'],
                    'event_schedule_id' => $validated['schedule_id'],
                ],
                [
                    'ratings'  => json_encode($request->ratings),
                    'comments' => $request->comments,
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Proof & feedback saved successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Upload proof failed', [
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
                'file'  => $e->getFile(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }


    public function getUploadedProof(Request $request)
    {
        try {
            $proofs = StudentUploadProof::with('event')->where([
                'student_id' => $request->student_id,
                'event_id'   => $request->event_id,
                'event_schedule_id' => $request->schedule_id
            ])
                ->whereHas('event', function ($query) {
                    $query->where('publish', 1)
                        ->where('is_active', 'y');
                })->get();

            $feedback = StudentFeedback::with('event')->where([
                'student_id' => $request->student_id,
                'event_id'   => $request->event_id,
                'event_schedule_id' => $request->schedule_id
            ])
                ->whereHas('event', function ($query) {
                    $query->where('publish', 1)
                        ->where('is_active', 'y');
                })->first();


            return response()->json([
                'proofs'   => $proofs,
                'feedback' => $feedback
            ]);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
