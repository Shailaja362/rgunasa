<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class StudentGuidelineSheet implements FromArray, WithHeadings, WithTitle
{
    public function headings(): array
    {
        return [
            'Field Name',
            'Instructions',
        ];
    }

    public function title(): string
    {
        return 'Student Upload Guideline Sheet';
    }

    public function array(): array
    {
        return [
            ['Register Number', 'Unique student register number'],
            ['Name', 'Full name as per records'],
            ['Email', 'Valid email address (example@domain.com)'],
            ['Gender', 'm / f / o'],
            ['Mobile Number', '10-digit mobile number,Unique Mobile Number'],
            ['Date of Birth', 'Format: YYYY-MM-DD, 2026-01-01'],
            ['Department', 'Refer Department Sheet'],
            ['Programme', 'Refer Programme Sheet'],
            ['Section', 'Allowed values: a, b, c, d, r'],
        ];
    }
}
