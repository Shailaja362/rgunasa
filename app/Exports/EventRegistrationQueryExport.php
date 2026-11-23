<?php

namespace App\Exports;

use App\Services\Reports\EventRegistrationReport;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Chunked Excel export: Laravel Excel reads the query 1,000 rows at a time
 * instead of loading every registration into memory at once.
 */
class EventRegistrationQueryExport implements FromQuery, WithMapping, WithHeadings, WithTitle, WithCustomChunkSize
{
    private int $serial = 0;

    public function __construct(private array $filters) {}

    public function query(): Builder
    {
        return EventRegistrationReport::exportQuery($this->filters, EventRegistrationReport::SPREADSHEET_RELATIONS)
            ->orderByDesc('id');
    }

    public function map($registration): array
    {
        return EventRegistrationReport::spreadsheetRow($registration, ++$this->serial);
    }

    public function headings(): array
    {
        return EventRegistrationReport::SPREADSHEET_HEADINGS;
    }

    public function title(): string
    {
        return 'Event Registered Students';
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
