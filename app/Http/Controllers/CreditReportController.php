<?php

namespace App\Http\Controllers;

use App\Exports\CreditReportExport;
use App\Models\Batch;
use App\Models\Programme;
use App\Services\Reports\StudentCreditReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Credit Report page (admin + super admin): semester-wise and
 * programme-wise required, earned and remaining credits per student.
 */
class CreditReportController extends Controller
{
    public function index(Request $request)
    {
        $this->data['programmes'] = Programme::orderBy('name')->get(['id', 'name']);
        $this->data['batches'] = Batch::orderBy('name')->pluck('name');
        $this->data['filters'] = $filters = StudentCreditReport::cleanFilters($request->all());
        $this->data['rows'] = collect();

        // Semester is required: the required credits are set per semester.
        if (!empty($filters['semester'])) {
            $required = StudentCreditReport::requiredCredits($filters['semester']);
            $students = StudentCreditReport::query($filters)->paginate(25)->withQueryString();
            $events = StudentCreditReport::creditedEvents($students->getCollection()->pluck('id'));
            $offset = ($students->currentPage() - 1) * $students->perPage();

            $this->data['students'] = $students;
            $this->data['required'] = $required;
            $this->data['rows'] = $students->getCollection()->values()
                ->map(fn($student, $i) => StudentCreditReport::row($student, $offset + $i + 1, $required, $events));
            $this->data['summary'] = $this->summary($filters, $required);
            $this->data['programmeName'] = isset($filters['programme_id'])
                ? $this->data['programmes']->firstWhere('id', (int) $filters['programme_id'])?->name
                : null;
        }

        return view('admin.student_credits.index')->with($this->data);
    }

    /** Excel (default) or PDF of every student matching the filters. */
    public function exportCredits(Request $request)
    {
        $filters = StudentCreditReport::cleanFilters($request->all());

        if (empty($filters['semester'])) {
            return redirect()
                ->route('credit_report', $request->except('type'))
                ->with('error', 'Please select a semester before downloading the report.');
        }

        // A PDF of every programme in a semester is thousands of rows and takes
        // ~30s (gateway timeout risk); Excel handles that in about a second.
        if ($request->type === 'pdf' && empty($filters['programme_id'])) {
            return redirect()
                ->route('credit_report', $request->except('type'))
                ->with('error', 'Please select a programme to download the PDF, or use Export Excel for all programmes.');
        }

        $required = StudentCreditReport::requiredCredits($filters['semester']);
        $rows = $this->allRows($filters, $required);

        $programme = isset($filters['programme_id']) ? Programme::find($filters['programme_id']) : null;
        $fileName = 'student-credits-sem-' . $filters['semester']
            . ($programme ? '-' . Str::slug($programme->name) : '')
            . (isset($filters['section']) ? '-sec-' . strtolower($filters['section']) : '');

        if ($request->type === 'pdf') {
            // Large semesters (every programme) can be a few thousand rows.
            set_time_limit(300);
            ini_set('memory_limit', '1024M');

            return Pdf::loadView('admin.student_credits.export_pdf', [
                'rows'          => $rows,
                'filters'       => $filters,
                'required'      => $required,
                'programmeName' => $programme?->name,
            ])->setPaper('a4', 'landscape')->download($fileName . '.pdf');
        }

        return Excel::download(new CreditReportExport($rows), $fileName . '.xlsx');
    }

    /** Every matching row, read in chunks so memory stays flat. */
    private function allRows(array $filters, ?float $required)
    {
        $rows = collect();
        $serial = 0;

        StudentCreditReport::query($filters)->chunk(500, function ($students) use (&$rows, &$serial, $required) {
            $events = StudentCreditReport::creditedEvents($students->pluck('id'));
            foreach ($students as $student) {
                $rows->push(StudentCreditReport::row($student, ++$serial, $required, $events));
            }
        });

        return $rows;
    }

    /** Totals across ALL matching students (not just the current page). */
    private function summary(array $filters, ?float $required): array
    {
        $earned = StudentCreditReport::query($filters)
            ->reorder()
            ->without('get_programme')
            ->pluck('earned_raw')
            ->map(fn($value) => min((float) $value, StudentCreditReport::MAX_EARNED));

        $completed = $required === null ? null : $earned->filter(fn($value) => $value >= $required)->count();

        return [
            'students'  => $earned->count(),
            'completed' => $completed,
            'pending'   => $completed === null ? null : $earned->count() - $completed,
        ];
    }
}
