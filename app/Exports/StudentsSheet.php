<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsSheet implements WithHeadings, WithTitle
{
    public function headings(): array
    {
        return [
            'S.No',
            'Register Number',
            'Name',
            'Email',
            'Gender',
            'Mobile Number',
            'Date of Birth',
            'Department',
            'Programme',
            'Section'
        ];
    }

    public function title(): string
    {
        return 'Student Upload Sheet';
    }

    public function array(): array
    {
        return []; // Empty rows
    }
}
