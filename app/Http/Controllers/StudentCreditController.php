<?php

namespace App\Http\Controllers;

use App\Models\CreditPoint;
use App\Models\Programme;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Exports\StudentCreditExport;
use Maatwebsite\Excel\Facades\Excel;


class StudentCreditController extends Controller
{
    public function index(Request $request){
        $this->data['programmes'] = Programme::get();
        if ($request->filled('programme_id') && $request->filled('semester')) {
                $this->data['student_credits'] =  Student::where([
                'programme_id' => $request->programme_id,
                'semester'     => $request->semester,
                'section'      => $request->section
            ])
                ->with([
                    'registrations.get_event_schedule.event'
                ])
                ->whereHas('registrations.get_event_schedule.event', function ($query) {
                    $query->where('publish', 1)
                        ->where('is_active', 'y');
                })
                ->get()
                ->map(function ($student) {
                    $student->earned_credits = $student->registrations
                        ->whereNotNull('grade')
                        ->where('grade', '!=', 'd')
                        ->sum(fn($reg) => $reg->get_event_schedule->credit_points ?? 0);
                    return $student;
                });
                $this->data['progm'] = Programme::where('id',$request->programme_id)->first();
                $this->data['credit_points'] = CreditPoint::where('semester', $request->semester)->first();
        }

        return view('student.student_credit_points')->with($this->data);
    }

    public function exportCredits(Request $request)
    {
        $students = Student::where([
            'programme_id' => $request->programme_id,
            'semester'     => $request->semester,
            'section'      => $request->section
        ])
            ->with([
                'registrations.get_event_schedule.event'
            ])
            ->whereHas('registrations.get_event_schedule.event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            })
            ->get()
            ->map(function ($student) {
                $student->earned_credits = $student->registrations
                    ->whereNotNull('grade')
                    ->where('grade', '!=', 'd')
                    ->sum(fn($reg) => $reg->get_event_schedule->credit_points ?? 0);
                return $student;
            });
        $prog = Programme::where('id', $request->programme_id)->first();
        $creditpoints = CreditPoint::where('semester', $request->semester)->first();
        return Excel::download(new StudentCreditExport($students, $creditpoints),'student_'. ($request->semester) .'_'. ($prog->name ?? '').'_credits'.".xlsx");
    }
}
