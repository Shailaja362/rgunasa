<?php

namespace App\Http\Controllers;

use App\Exports\DepartmentStudentReportExport;
use App\Models\Department;
use App\Models\Event;
use App\Models\Programme;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class DepartmentStudentReportController extends Controller
{
    // Registrations with this status are not counted as registered
    private const CANCELLED_STATUS = 4;

    public function index(Request $request)
    {
        $this->data['events'] = $this->visibleEvents()->get();
        $this->data['programmes'] = Programme::orderBy('name')->get(['id', 'name']);

        $this->data['summary'] = $this->departmentSummary($request);

        $this->data['students'] = $this->studentQuery($request)
            ->orderBy('department_id')
            ->orderBy('name')
            ->paginate(10)
            ->appends($request->all());

        return view('admin.department_student_report_index')->with($this->data);
    }

    public function export(Request $request)
    {
        $this->data['summary'] = $this->departmentSummary($request);
        $this->data['students'] = $this->studentQuery($request)
            ->orderBy('department_id')
            ->orderBy('name')
            ->get();
        $this->data['eventTitle'] = $request->filled('event_id')
            ? optional(Event::find($request->event_id))->title
            : null;

        if ($request->type === 'excel') {
            return Excel::download(
                new DepartmentStudentReportExport($this->data['summary'], $this->data['students']),
                'department-wise-students.xlsx'
            );
        }

        if ($request->type === 'pdf') {
            return Pdf::loadView('admin.department_student_report.export_pdf', $this->data)
                ->download('department-wise-students.pdf');
        }

        if ($request->type === 'word') {
            return response()
                ->view('admin.department_student_report.export_word', $this->data)
                ->header('Content-Type', 'application/msword')
                ->header('Content-Disposition', 'attachment; filename="department-wise-students.doc"');
        }

        return redirect()->route('department_student_report');
    }

    private function visibleEvents()
    {
        return Event::where([
            'publish' => 1,
            'is_active' => 'y'
        ])
            ->when(empty(session()->get('super_admin')), function ($q) {
                $q->where('created_by', Auth::guard('admin')->id());
            });
    }

    /**
     * Constraint on the registrations relation that decides whether a student
     * counts as registered: the selected event, or any event this admin can see.
     */
    private function registrationConstraint(Request $request)
    {
        $eventIds = $request->filled('event_id')
            ? [$request->event_id]
            : $this->visibleEvents()->pluck('id')->all();

        return function ($q) use ($eventIds, $request) {
            $q->where('status', '!=', self::CANCELLED_STATUS)
                ->whereIn('event_id', $eventIds)
                ->when($request->filled('event_date'), function ($registrationQuery) use ($request) {
                    $registrationQuery->whereHas('get_event_schedule', function ($scheduleQuery) use ($request) {
                        $scheduleQuery->whereDate('event_date', $request->event_date);
                    });
                });
        };
    }

    private function baseStudentQuery(Request $request)
    {
        return Student::query()
            ->when($request->programme_id, fn($q) => $q->where('programme_id', $request->programme_id));
    }

    private function studentQuery(Request $request)
    {
        $constraint = $this->registrationConstraint($request);

        return $this->baseStudentQuery($request)
            ->with(['get_department', 'get_programme'])
            ->withExists(['registrations as is_registered' => $constraint]);
    }

    private function departmentSummary(Request $request)
    {
        $totals = $this->baseStudentQuery($request)
            ->selectRaw('department_id, COUNT(*) as total')
            ->groupBy('department_id')
            ->pluck('total', 'department_id');

        $registered = $this->baseStudentQuery($request)
            ->whereHas('registrations', $this->registrationConstraint($request))
            ->selectRaw('department_id, COUNT(*) as total')
            ->groupBy('department_id')
            ->pluck('total', 'department_id');

        return Department::whereIn('id', $totals->keys())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function ($department) use ($totals, $registered) {
                $total = (int) $totals[$department->id];
                $registeredCount = (int) ($registered[$department->id] ?? 0);

                return (object) [
                    'name' => $department->name,
                    'total' => $total,
                    'registered' => $registeredCount,
                    'unregistered' => $total - $registeredCount,
                ];
            });
    }
}
