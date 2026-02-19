<?php

namespace App\Exports;

use App\Models\Designation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DesignationSheet implements FromCollection, WithHeadings, WithTitle
{
    public function collection()
    {
        return Designation::select('id', 'name')->get();
    }

    public function headings(): array
    {
        return ['ID', 'Designation Name'];
    }

    public function title(): string
    {
        return 'Designation';
    }
}
