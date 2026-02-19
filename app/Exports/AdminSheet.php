<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class AdminSheet implements WithHeadings, WithTitle
{
    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Role',
            'Mobile Number',
            'Employee Code',
            'Security Code',
            'Department Name',
            'Designation Name'
        ];
    }

    public function title(): string
    {
        return 'Admin Upload Sheet';
    }

    public function array(): array
    {
        return []; // Empty rows
    }
}
