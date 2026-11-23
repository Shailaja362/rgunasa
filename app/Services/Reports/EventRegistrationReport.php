<?php

namespace App\Services\Reports;

use App\Models\StudentEventRegistration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Shared query for the "Event Registered Students" report, used by the report
 * page and by the queued export job so both always apply the same filters.
 */
class EventRegistrationReport
{
    public const FILTERS = ['event_id', 'status', 'from_date', 'to_date', 'search', 'batch', 'semester', 'programme_id'];

    public const STATUS_LABELS = [
        1 => 'Registered',
        2 => 'Approved',
        3 => 'Completed',
        4 => 'Cancelled',
    ];

    /** Relations the spreadsheet formats (Excel/CSV) print. */
    public const SPREADSHEET_RELATIONS = [
        'event:id,title',
        'student:id,name,email,register_number,section,department_id',
        'student.get_department:id,name',
        'get_event_schedule:id,batch,semester',
    ];

    /** Relations the document formats (PDF/Word) print. */
    public const DOCUMENT_RELATIONS = [
        'event:id,title',
        'student:id,name,email,batch,semester',
    ];

    public const SPREADSHEET_HEADINGS = [
        'S.No',
        'Register Number',
        'Student Name',
        'Batch',
        'Semester',
        'Department',
        'Section',
        'Email',
        'Event',
        'Status',
        'Registered At',
    ];

    /** One spreadsheet row, same columns as the original Excel/CSV export. */
    public static function spreadsheetRow(StudentEventRegistration $registration, int $serial): array
    {
        return [
            $serial,
            optional($registration->student)->register_number ?? '-',
            optional($registration->student)->name ?? '-',
            optional($registration->get_event_schedule)->batch ?? '-',
            optional($registration->get_event_schedule)->semester ?? '-',
            optional(optional($registration->student)->get_department)->name ?? '-',
            optional($registration->student)->section ?? '-',
            optional($registration->student)->email ?? '-',
            optional($registration->event)->title ?? '-',
            self::STATUS_LABELS[$registration->status] ?? 'Unknown',
            optional($registration->created_at)->format('d-m-Y h:i A') ?? '-',
        ];
    }

    /** Only the filter keys, with empty values removed. */
    public static function cleanFilters(array $input): array
    {
        return collect($input)
            ->only(self::FILTERS)
            ->map(fn($value) => is_string($value) ? trim($value) : $value)
            ->filter(fn($value) => $value !== null && $value !== '')
            ->all();
    }

    /** Export query: registrations for published, active events, filtered. */
    public static function exportQuery(array $filters, array $relations): Builder
    {
        $query = StudentEventRegistration::with($relations)
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            });

        return self::applyFilters($query, $filters);
    }

    public static function applyFilters($query, array $filters)
    {
        $request = new Request($filters);

        $query->when($request->event_id, fn($q) => $q->where('event_id', $request->event_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->from_date, function ($q) use ($request) {
                $q->whereHas('get_event_schedule', function ($schedule) use ($request) {
                    $schedule->whereDate('event_date', 'like', '%' . $request->from_date . '%');
                });
            })
            ->when($request->to_date, function ($q) use ($request) {
                $q->whereHas('get_event_schedule', function ($schedule) use ($request) {
                    $schedule->whereDate('event_date', 'like', '%' . $request->to_date . '%');
                });
            })
            ->when($request->search, function ($q) use ($request) {
                $q->whereHas('student', function ($student) use ($request) {
                    $student->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            })
            ->when($request->programme_id, function ($q) use ($request) {
                $q->whereHas('student', fn($student) => $student->where('programme_id', $request->programme_id));
            });

        $query->when($request->filled('semester') || $request->filled('batch'), function ($q) use ($request) {
            $semester = $request->semester;
            $batch = $request->batch;
            $isFirstYearFilter = in_array((int) $semester, [1, 2]);

            $q->whereHas('student', function ($studentQ) use ($semester, $batch) {
                if (!empty($semester)) {
                    $studentQ->where('semester', $semester);
                }

                if (!empty($batch)) {
                    $studentQ->where('batch', $batch);
                }
            });

            $q->whereHas('get_event_schedule', function ($scheduleQ) use ($semester, $batch, $isFirstYearFilter) {
                $scheduleQ->where(function ($mainQ) use ($semester, $batch, $isFirstYearFilter) {
                    $mainQ->where(function ($normalQ) use ($semester, $batch) {
                        // event_schedules can hold several semesters/batches per
                        // row here, so use the model scopes, not a plain where.
                        if (!empty($semester)) {
                            $normalQ->openToSemester($semester);
                        }
                        if (!empty($batch)) {
                            $normalQ->openToBatch($batch);
                        }
                    });

                    if ($isFirstYearFilter) {
                        $mainQ->orWhere(function ($commonQ) use ($batch) {
                            $commonQ->whereNull('programme_id')
                                ->whereNull('section')
                                ->whereNull('semester')
                                ->openToBatch($batch);
                        });
                    }
                });
            });
        });

        return $query;
    }
}
