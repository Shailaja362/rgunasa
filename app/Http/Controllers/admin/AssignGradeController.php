<?php

namespace App\Http\Controllers\admin;

use App\Models\Event;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Models\StudentFeedback;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\StudentAttendance;
use App\Models\StudentUploadProof;
use App\Http\Controllers\Controller;
use App\Models\StudentEventRegistration;

class AssignGradeController extends Controller
{
    public function index()
    {
        $this->data['events'] = Event::with('get_club')->get();
        return view('admin.assign_grade_index')->with($this->data);
    }

    public function gradeEntry(Request $request)
    {
        $this->data['registrations'] = StudentAttendance::with([
            'student',
            'student.get_department',
            'get_student_upload_proof',
            'get_feedback',
            'grades' => function ($q) use ($request) {
                $q->where('event_id', $request->event_id);
            }
        ])
            ->whereHas('grades', function ($query) use ($request) {
                $query->where('event_id', $request->event_id);
            })
            ->whereNotNull('entry_time')
            ->whereNotNull('exit_time')
            ->where('event_id', $request->event_id)
            ->orderBy('id')
            ->get();
        $this->data['event'] = Event::find($request->event_id);
        $this->data['attendance_entry'] = StudentAttendance::where('event_id', $request->event_id)->get();
        return view('admin.assign_grade_entry')->with($this->data);
    }

    public function saveGrades(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'grades'   => 'required|array',
        ]);

        $eventId = $request->event_id;

        foreach ($request->grades as $registrationId => $grade) {


            $registration = StudentEventRegistration::where('student_id', $registrationId)
                ->where('event_id', $eventId)
                ->first();
            if ($registration) {
                $registration->grade = $grade;
                $registration->status = 2;
                $registration->save();
            }
        }

        return redirect()->back()->with('success', 'Grades assigned successfully.');
    }

    public function downloadEventReport(Request $request)
    {
        $student = Student::findOrFail($request->student);
        $event   = Event::findOrFail($request->event);
        $proofs = StudentUploadProof::where([
            'student_id' => $request->student,
            'event_id'   => $request->event,
        ])->get();

        $feedback = StudentFeedback::where([
            'student_id' => $request->student,
            'event_id'   => $request->event,
        ])->firstOrFail();

        $pdf = Pdf::loadView('student.pdf.student_event_report', compact(
            'student',
            'event',
            'proofs',
            'feedback'
        ))->setPaper('a4', 'portrait');

        return $pdf->stream('event-report.pdf');
    }
}
