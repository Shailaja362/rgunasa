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
        return $this->students->map(function ($student, $index) {
            $events = $student->registrations->map(function ($registration) {
                return $registration->get_event_schedule->event->title . ' - ' . (isset($registration->get_event_schedule->credit_points) ? round($registration->get_event_schedule->credit_points) : '')  ?? '';
            })->implode(', ');
            if ($student->earned_credits > 4) {
                $earned = 4;
            } else {
                $earned = $student->earned_credits;
            }
            $pending = $this->creditpoints->credit_points - $earned;
            return [
                'S.No' => $index + 1,
                'Register Number' => $student->register_number,
                'Student Name' => $student->name,
                'Programme' => $student->get_programme?->name,
                'Section' => $student->section,
                'Semester' => $student->semester,
                'Attended Events' => $events ?: 'No Events',
                'Semester Credit Points' => (isset($this->creditpoints->credit_points) ? round($this->creditpoints->credit_points) : '')  ?? '',
                'Earned Credit Points' => $student->earned_credits ?? 0,
                'Pending Credit Points' => $pending
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
            'Semester Credit Points',
            'Earned Credit Points',
            'Pending Credit Points',
        ];
    }
}
