<?php

namespace App\Exports;

use App\Models\EventRegistration;
use App\Models\StudentEventRegistration;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class EventRegistrationExport implements FromCollection, WithHeadings, WithTitle
{
    protected array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        $statusLabels = [
            1 => 'Registered',
            2 => 'Approved',
            3 => 'Completed',
            4 => 'Cancelled',
        ];

        return StudentEventRegistration::with([
            'event',
            'student',
            'student.get_department',
            'get_event_schedule'
        ])
            ->when(
                $this->filters['event_id'] ?? null,
                fn($q, $v) => $q->where('event_id', $v)
            )
            ->when(
                $this->filters['status'] ?? null,
                fn($q, $v) => $q->where('status', $v)
            )
            ->when(
                $this->filters['from_date'] ?? null,
                function ($q, $fromDate) {
                    $q->whereHas('get_event_schedule', function ($schedule) use ($fromDate) {
                        $schedule->whereDate('event_date', '>=', $fromDate);
                    });
                }
            )
            ->when(
                $this->filters['to_date'] ?? null,
                function ($q, $toDate) {
                    $q->whereHas('get_event_schedule', function ($schedule) use ($toDate) {
                        $schedule->whereDate('event_date', '<=', $toDate);
                    });
                }
            )
            ->when(
                $this->filters['search'] ?? null,
                function ($q, $search) {
                    $q->whereHas('student', function ($student) use ($search) {
                        $student->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
                }
            )
            ->when(
                $this->filters['batch'] ?? null,
                function ($q, $batch) {
                    $q->whereHas('get_event_schedule', function ($schedule) use ($batch) {
                        $schedule->where('batch', 'like', '%' . $batch . '%');
                    });
                }
            )
            ->when(
                $this->filters['semester'] ?? null,
                function ($q, $semester) {
                    $q->whereHas('get_event_schedule', function ($schedule) use ($semester) {
                        $schedule->where('semester', 'like', '%' . $semester . '%');
                    });
                }
            )
            ->latest()
            ->get()
            ->map(function ($row, $index) use ($statusLabels) {
                return [
                    'S.No'            => $index + 1,
                    'Register Number' => $row->student->register_number ?? '',
                    'Student Name'    => $row->student->name ?? '',
                    'Batch'           => $row->student->batch ?? '',
                    'Semester'        => $row->student->semester ?? '',
                    'Department'      => $row->student->get_department->name ?? '',
                    'Section'         => $row->student->section ?? '',
                    'Email'           => $row->student->email ?? '',
                    'Event'           => $row->event->title ?? '',
                    'Status'          => $statusLabels[$row->status] ?? '',
                    'Registered At'   => optional($row->created_at)->format('d-m-Y'),
                ];
            });
    }

    public function headings(): array
    {
        return [
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
    }

    public function title(): string
    {
        return 'Event Registered Students';
    }
}
