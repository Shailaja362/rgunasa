<?php

namespace App\Services\Reports;

use App\Models\CreditPoint;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Semester-wise / programme-wise credit report: for every student, the credits
 * the semester requires, what they have earned and what is still remaining.
 * Used by the report page and by the Excel and PDF exports, so all three
 * always show the same numbers.
 *
 * Earned credits follow the student dashboard's rule: the event_schedule's
 * credit_points for each graded registration (any grade except D) on a
 * published, active event whose schedule is open to the student's own
 * semester, batch and programme.
 */
class StudentCreditReport
{
    /** Same cap the student app and dashboard apply to earned credits. */
    public const MAX_EARNED = 4;

    public const FILTERS = ['semester', 'programme_id', 'section', 'batch'];

    public const HEADINGS = [
        'S.No',
        'Register Number',
        'Student Name',
        'Programme',
        'Section',
        'Batch',
        'Semester',
        'Credited Events',
        'Required Credits',
        'Earned Credits',
        'Remaining Credits',
        'Status',
    ];

    /** Only the filter keys, with empty values removed. */
    public static function cleanFilters(array $input): array
    {
        return collect($input)
            ->only(self::FILTERS)
            ->map(fn($value) => is_string($value) ? trim($value) : $value)
            ->filter(fn($value) => $value !== null && $value !== '')
            ->all();
    }

    /** Credits the semester requires (Credit Point Assign), or null if not set. */
    public static function requiredCredits(?string $semester): ?float
    {
        if (empty($semester)) {
            return null;
        }

        $value = CreditPoint::where('semester', $semester)->value('credit_points');

        return $value === null ? null : (float) $value;
    }

    /**
     * Students matching the filters, each with an `earned_raw` column holding
     * their uncapped earned credits, worked out in the same SQL query.
     */
    public static function query(array $filters): Builder
    {
        $earned = self::creditedRegistrations('students')
            ->whereColumn('r.student_id', 'students.id')
            ->selectRaw('COALESCE(SUM(es.credit_points), 0)');

        return Student::query()
            ->select([
                'students.id',
                'students.name',
                'students.register_number',
                'students.programme_id',
                'students.section',
                'students.batch',
                'students.semester',
            ])
            ->selectSub($earned, 'earned_raw')
            ->with('get_programme:id,name')
            ->when($filters['semester'] ?? null, fn($q, $v) => $q->where('students.semester', $v))
            ->when($filters['programme_id'] ?? null, fn($q, $v) => $q->where('students.programme_id', $v))
            ->when($filters['section'] ?? null, fn($q, $v) => $q->where('students.section', $v))
            ->when($filters['batch'] ?? null, fn($q, $v) => $q->where('students.batch', $v))
            ->orderBy('students.programme_id')
            ->orderBy('students.section')
            ->orderBy('students.register_number');
    }

    /**
     * "Event title (credits)" for each student's credited events, keyed by
     * student id. One query for the whole page / chunk.
     */
    public static function creditedEvents(Collection $studentIds): Collection
    {
        if ($studentIds->isEmpty()) {
            return collect();
        }

        return self::creditedRegistrations('s')
            ->join('students as s', 's.id', '=', 'r.student_id')
            ->whereIn('r.student_id', $studentIds->all())
            ->orderBy('es.event_date')
            ->get(['r.student_id', 'e.title', 'es.credit_points'])
            ->groupBy('student_id')
            ->map(fn($rows) => $rows
                ->map(fn($row) => $row->title . ' (' . self::number((float) $row->credit_points) . ')')
                ->implode(', '));
    }

    /** One report row: the same columns on the page, in Excel and in the PDF. */
    public static function row(Student $student, int $serial, ?float $required, Collection $events): array
    {
        $earned = min((float) $student->earned_raw, self::MAX_EARNED);
        $remaining = $required === null ? null : max(0, $required - $earned);

        return [
            'serial'          => $serial,
            'register_number' => $student->register_number ?: '-',
            'name'            => $student->name,
            'programme'       => $student->get_programme->name ?? '-',
            'section'         => strtoupper((string) $student->section) ?: '-',
            'batch'           => $student->batch ?: '-',
            'semester'        => $student->semester ?: '-',
            'events'          => $events->get($student->id) ?: 'No credited events',
            'required'        => $required === null ? 'Not set' : self::number($required),
            'earned'          => self::number($earned),
            'remaining'       => $remaining === null ? '-' : self::number($remaining),
            'status'          => $remaining === null ? '-' : ($remaining <= 0 ? 'Completed' : 'Pending'),
        ];
    }

    /** 2.00 -> "2", 1.50 -> "1.5". */
    public static function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /**
     * Graded (not D) registrations on published, active events whose schedule
     * is open to the student's semester, batch and programme.
     * `$student` is the SQL alias/table name of the students table.
     */
    private static function creditedRegistrations(string $student): QueryBuilder
    {
        return DB::table('student_event_registrations as r')
            ->join('event_schedules as es', 'es.id', '=', 'r.event_schedule_id')
            ->join('events as e', 'e.id', '=', 'r.event_id')
            ->whereNotNull('r.grade')
            ->whereRaw("LOWER(r.grade) <> 'd'")
            ->where('e.publish', 1)
            ->where('e.is_active', 'y')
            ->whereRaw("(es.semester IS NULL OR es.semester = '' OR FIND_IN_SET({$student}.semester, es.semester))")
            ->whereRaw("(es.batch IS NULL OR es.batch = '' OR FIND_IN_SET({$student}.batch, es.batch))")
            ->whereRaw("(es.programme_id IS NULL OR es.programme_id = '' OR FIND_IN_SET({$student}.programme_id, es.programme_id))");
    }
}
