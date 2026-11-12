<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\StudentEventRegistration;
use App\Models\StudentFeedback;
use App\Models\StudentUploadProof;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    public function registerEvent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_id'    => 'required|integer|exists:events,id',
            'student_id' => 'required|integer|exists:students,id',
            'event_schedule_id' => 'required|integer|exists:event_schedules,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 409,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 409);
        }

        // Use the authenticated student, never trust student_id from the request body.
        $student = auth('student-api')->user();
        if (!$student || (int) $request['student_id'] !== (int) $student->id) {
            return response()->json([
                'status'  => 401,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Same rules as the web StudentRegisterEvent (RegisterEventController::studentRegisterEvent).
        DB::beginTransaction();
        try {
            $event = Event::findOrFail($request['event_id']);
            $schedule = EventSchedule::where('id', $request['event_schedule_id'])
                ->where('event_id', $event->id)
                ->lockForUpdate()
                ->first();

            if (!$schedule) {
                DB::rollBack();
                return $this->registerError('Invalid event schedule for this event.');
            }

            $lastRegistration = StudentEventRegistration::where([
                'student_id'        => $student->id,
                'event_id'          => $event->id,
                'event_schedule_id' => $schedule->id,
            ])
                ->orderBy('registered_at', 'desc')
                ->first();

            if ($lastRegistration && (empty($event->duration_days) || $event->duration_days == 0)) {
                DB::rollBack();
                return $this->registerError('You are already registered for this event.');
            }

            if ($lastRegistration && $event->duration_days > 0) {
                $nextAllowedDate = Carbon::parse($lastRegistration->registered_at)
                    ->addDays($event->duration_days);
                if (Carbon::now()->lt($nextAllowedDate)) {
                    DB::rollBack();
                    return $this->registerError('You can register again after ' . $nextAllowedDate->format('d M Y'));
                }
            }

            // seat_count is one shared pool for the whole schedule row, so count
            // every registration on it, not just the student's cohort.
            $registeredCount = StudentEventRegistration::where('event_schedule_id', $schedule->id)->count();
            if ($registeredCount >= $schedule->seat_count) {
                DB::rollBack();
                return $this->registerError('This event seat is already full.');
            }

            StudentEventRegistration::create([
                'student_id'        => $student->id,
                'event_id'          => $event->id,
                'event_schedule_id' => $schedule->id,
                'status'            => 1,
                'registered_at'     => Carbon::now(),
            ]);

            DB::commit();

            return response()->json([
                'status'  => 200,
                'message' => 'Registration successful',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('API event registration failed', ['student_id' => $student->id, 'error' => $e->getMessage()]);
            return response()->json([
                'status'  => 500,
                'message' => 'Registration failed.',
            ], 500);
        }
    }

    private function registerError(string $message)
    {
        return response()->json([
            'status'  => 409,
            'message' => $message,
        ], 409);
    }

    public function eventUploadProof(Request $request)
    {
       $validator = Validator::make($request->all(), [
           'student_id'      => 'required|integer',
           'event_id'        => 'required|integer',
           'schedule_id'     => 'required|integer|exists:event_schedules,id',
           'proof.*'         => 'nullable|file|max:10240',
           'ratings'         => 'required|array',
           'ratings.*'       => 'required|integer|min:1|max:5',
           'comments'        => 'nullable|string|max:500',
           'removed_proofs'  => 'nullable|array',

       ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 409,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 409);
        }

        $validated = $validator->validated();
        $student = Student::find($validated['student_id']);

        $schedule = EventSchedule::where('id', $validated['schedule_id'])
            ->where('event_id', $validated['event_id'])
            ->where(function ($q) use ($student) {
                $this->applyStudentScheduleFilter($q, $student);
            })
            ->first();

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid event schedule for this student.',
            ], 422);
        }

        /* ================= Delete removed proofs ================= */
        if (!empty($validated['removed_proofs'])) {
            $proofs = StudentUploadProof::whereIn('id', $validated['removed_proofs'])
                ->where('student_id', $student->id)
                ->where('event_id', $validated['event_id'])
                ->where('event_schedule_id', $validated['schedule_id'])
                ->get();

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
                    'student_id'        => $student->id,
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
                'student_id'        => $student->id,
                'event_id'          => $validated['event_id'],
                'event_schedule_id' => $validated['schedule_id'],
            ],
            [
                'ratings'  => json_encode($request->ratings),
                'comments' => $request->comments,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Proof & feedback saved successfully',
        ]);


    }

    private function applyStudentScheduleFilter($q, $student)
    {
        $isFirstYearStudent = in_array((int) $student->semester, [1, 2]);

        $q->where(function ($subQ) use ($student, $isFirstYearStudent) {

            // Student-specific events
            $subQ->where(function ($normalQ) use ($student) {
                $normalQ->openToProgramme($student->programme_id)
                    ->openToSection($student->section)
                    ->openToBatch($student->batch)
                    ->openToSemester($student->semester);
            });

            // Common first year events
            if ($isFirstYearStudent) {
                $subQ->orWhere(function ($firstYearQ) use ($student) {
                    $firstYearQ->whereNull('programme_id')
                        ->whereNull('section')
                        ->whereNull('semester')
                        ->openToBatch($student->batch);
                });
            }
        });
    }


}
