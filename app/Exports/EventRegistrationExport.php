<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class EventRegistrationExport implements FromCollection, WithHeadings, WithTitle
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function collection(): Collection
    {
        $registrations = collect($this->data['registrations'] ?? []);
        $statusLabels = $this->data['statusLabels'] ?? [];

        return $registrations->values()->map(function ($registration, $index) use ($statusLabels) {
            return [
                'S.No'            => $index + 1,
                'Register Number' => optional($registration->student)->register_number ?? '-',
                'Student Name'    => optional($registration->student)->name ?? '-',
                'Batch'           => optional($registration->get_event_schedule)->batch ?? '-',
                'Semester'        => optional($registration->get_event_schedule)->semester ?? '-',
                'Department'      => optional(optional($registration->student)->get_department)->name ?? '-',
                'Section'         => optional($registration->student)->section ?? '-',
                'Email'           => optional($registration->student)->email ?? '-',
                'Event'           => optional($registration->event)->title ?? '-',
                'Status'          => $statusLabels[$registration->status] ?? 'Unknown',
                'Registered At'   => optional($registration->created_at)->format('d-m-Y h:i A') ?? '-',
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
