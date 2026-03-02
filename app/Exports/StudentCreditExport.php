<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentCreditExport implements FromCollection, WithHeadings
{
    protected $students;
    protected $creditpoints;

    public function __construct($students, $creditpoints)
    {
        $this->students = $students;
        $this->creditpoints = $creditpoints;
    }

    public function collection()
    {
        return $this->students->map(function ($student, $index)  {
            $events = $student->registrations->map(function ($registration) {
                return $registration->get_event_schedule->event->title .' - ' . (isset($registration->get_event_schedule->credit_points) ? round($registration->get_event_schedule->credit_points) : '')  ?? '';
            })->implode(', ');
            return [
                'S.No' => $index + 1,
                'Register Number' => $student->register_number,
                'Student Name' => $student->name,
                'Programme' => $student->get_programme?->name,
                'Section' => $student->section,
                'Semester' => $student->semester,
                'Attended Events' => $events ?: 'No Events',
                'Earned Credit Points' => $student->earned_credits ?? 0,
                'Semester Credit Points' => (isset($this->creditpoints->credit_points) ? round($this->creditpoints->credit_points) : '')  ?? ''
            ];
        });
    }

    public function headings(): array
    {
        return [
            'S.No',
            'Register Number',
            'Student Name',
            'Programme',
            'Section',
            'Semester',
            'Attended Events',
            'Earned Credit Points',
            'Semester Credit Points'
        ];
    }
}
