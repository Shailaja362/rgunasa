<?php

namespace App\Http\Controllers\student;

use App\Models\Student;
use App\Helpers\ActivityLog;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use App\Models\StudentEventRegistration;

class CertificatesController extends Controller
{
    public function index()
    {
        $student = Auth::guard('student')->user();
        $this->data['completedEvents'] = StudentEventRegistration::with([
            'event',
            'student',
            'get_event_attendance'
        ])
            ->whereStudentId($student->id)
            ->whereNotNull('grade')
            ->where('grade', '!=', 'd')
            ->whereHas(
                'get_event_attendance',
                fn($query) =>
                $query->whereNotNull('entry_time')
                    ->whereNotNull('exit_time')
            )
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })
            ->get();


        return view('student.certificates.index')->with($this->data);
    }

    public function downloadCertificate(Request $request)
    {
        $event = Event::with('get_admin')->where('id', $request->event_id)->first();
        $registration = StudentEventRegistration::with('event')->where('event_id', $event->id)
            ->where('student_id', $request->student_id)
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })
            ->first();
        $student = Student::with('get_department')->where('id',$request->student_id)->first();
        $pdf = Pdf::loadView('student.certificates.template', [
            'event' =>  $event,
            'student' => $student,
            'registration' => $registration,
         ]);
        $filename = 'certificate-' . $event->title ?? '' . '-' . $student->name ?? '' . '.pdf';
        $user = auth('student')->user();
        ActivityLog::add($student->name . ' - ' . $event->title . " - Certificate Downloaded", auth('student')->user());
        return $pdf->stream($filename);
    }
}
