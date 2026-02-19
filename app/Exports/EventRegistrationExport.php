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

        return StudentEventRegistration::with(
            [
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
            ->when($this->filters['from_date'], function ($q) {
                $q->whereHas('get_event_schedule', function ($schedule) {
                    $schedule->whereDate('event_date', 'like', '%' . $this->filters['from_date'] . '%');
                });
            })
            ->when($this->filters['to_date'], function ($q) {
                $q->whereHas('get_event_schedule', function ($schedule) {
                    $schedule->whereDate('event_date', 'like', '%' . $this->filters['to_date'] . '%');
                });
            })
            ->when($this->filters['search'], function ($q) {
                $q->whereHas('student', function ($student) {
                    $student->where('name', 'like', '%' . $this->filters['search'] . '%')
                        ->orWhere('email', 'like', '%' . $this->filters['search'] . '%');
                });
            })
            ->when($this->filters['batch'], function ($q)  {
                $q->whereHas('get_event_schedule', function ($schedule) {
                    $schedule->where('batch', 'like', '%' . $this->filters['batch'] . '%');
                });
            })
            ->when($this->filters['semester'], function ($q) {
                $q->whereHas('get_event_schedule', function ($schedule) {
                    $schedule->where('semester', 'like', '%' . $this->filters['semester'] . '%');
                });
            })
            ->latest()
            ->get()
            ->map(function ($row, $index) use ($statusLabels) {
                return [
                    'S.No'         => $index + 1,
                    'Register Number' => $row->student->register_number ?? '',
                    'Student Name' => $row->student->name ?? '',
                    'Batch' => $row->student->batch ?? '',
                    'Semester' => $row->student->semester ?? '',
                    'Department' => $row->student->get_department->name ?? '',
                    'Section' => $row->student->section ?? '',
                    'Email' => $row->student->email ?? '',
                    'Event' => $row->event->title ?? '',
                    'Status' => $statusLabels[$row->status] ?? '',
                    'Registered At' => $row->created_at->format('d-m-Y'),
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
