<?php

namespace App\Exports;

use App\Models\StudentAttendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttendanceExport implements FromCollection, WithHeadings
{
    protected $event_id;
    protected array $filters;

    /**
     * @param  array{programme_id?: mixed, section?: mixed, event_date?: mixed, batch?: array, semester?: array}  $filters
     */
    public function __construct($event_id, array $filters = [])
    {
        $this->event_id = $event_id;
        $this->filters = $filters;
    }

    public function collection()
    {
        $batches = array_filter((array) ($this->filters['batch'] ?? []));
        $semesters = array_filter((array) ($this->filters['semester'] ?? []));

        return StudentAttendance::with('student')
            ->where('event_id', $this->event_id)
            ->when(!empty($this->filters['programme_id']), function ($q) {
                $q->whereHas('student', fn($s) => $s->where('programme_id', $this->filters['programme_id']));
            })
            ->when(!empty($this->filters['section']), function ($q) {
                $q->whereHas('student', fn($s) => $s->where('section', $this->filters['section']));
            })
            ->when(!empty($batches), function ($q) use ($batches) {
                $q->whereHas('student', fn($s) => $s->whereIn('batch', $batches));
            })
            ->when(!empty($semesters), function ($q) use ($semesters) {
                $q->whereHas('student', fn($s) => $s->whereIn('semester', $semesters));
            })
            ->when(!empty($this->filters['event_date']), function ($q) {
                $q->whereHas('schedule', fn($s) => $s->whereDate('event_date', $this->filters['event_date']));
            })
            ->get()
            ->map(function ($row, $index) {
                return [
                    'S.No'       => $index + 1,
                    'Name'       => $row->student->name ?? '',
                    'Department' => $row->student?->get_department?->name ?? '',
                    'Section'    => $row->student?->section ?? '',
                    'Phone'      => $row->student?->mobile_number ?? '',
                    'Email'      => $row->student?->email ?? '',
                    'Entry Time' => $row->entry_time ?? '',
                    'Exit Time'  => $row->exit_time ?? '',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'S.No',
            'Name',
            'Department',
            'Section',
            'Phone',
            'Email',
            'Entry Time',
            'Exit Time',
        ];
    }
}
