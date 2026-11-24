<?php

namespace App\Exports;

use App\Services\Reports\StudentCreditReport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/** Student Credits report as Excel; rows come from StudentCreditReport::row(). */
class CreditReportExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(private Collection $rows) {}

    public function collection()
    {
        return $this->rows->map(fn(array $row) => array_values($row));
    }

    public function headings(): array
    {
        return StudentCreditReport::HEADINGS;
    }

    public function title(): string
    {
        return 'Student Credits';
    }
}
