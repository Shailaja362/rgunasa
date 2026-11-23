<?php

namespace App\Jobs;

use App\Exports\EventRegistrationQueryExport;
use App\Models\ReportExport;
use App\Services\Reports\EventRegistrationReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Builds the "Event Registered Students" report in the background, reading
 * registrations in chunks so no format ever loads the whole report at once.
 *
 *  - CSV / Word: rows are appended to the file chunk by chunk.
 *  - Excel:      Laravel Excel's chunked FromQuery export.
 *  - PDF:        one PDF part per chunk (DomPDF memory is freed between
 *                parts); several parts are delivered together as a ZIP.
 */
class GenerateRegistrationReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const CHUNK_SIZE = 1000;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(public ReportExport $export) {}

    public function handle(): void
    {
        ini_set('memory_limit', '1024M');

        $this->export->update(['status' => ReportExport::STATUS_PROCESSING, 'error' => null]);

        $filters = $this->export->filters ?? [];
        $total = EventRegistrationReport::exportQuery($filters, [])->count();
        $this->export->update(['total_rows' => $total]);

        $dir = 'exports/registrations/' . $this->export->id;
        Storage::disk('local')->deleteDirectory($dir);
        Storage::disk('local')->makeDirectory($dir);

        $baseName = 'event-registrations-' . now()->format('Y-m-d-His');

        [$relativePath, $fileName] = match ($this->export->type) {
            'csv'   => $this->buildCsv($filters, $dir, $baseName),
            'excel' => $this->buildExcel($filters, $dir, $baseName),
            'word'  => $this->buildWord($filters, $dir, $baseName),
            'pdf'   => $this->buildPdf($filters, $dir, $baseName, $total),
            default => throw new RuntimeException('Unsupported export type: ' . $this->export->type),
        };

        $this->export->update([
            'status'       => ReportExport::STATUS_COMPLETED,
            'file_path'    => $relativePath,
            'file_name'    => $fileName,
            'completed_at' => now(),
        ]);
    }

    public function failed(?Throwable $e): void
    {
        Log::error('Registration report export failed', [
            'export_id' => $this->export->id,
            'error'     => $e?->getMessage(),
        ]);

        $this->export->update([
            'status' => ReportExport::STATUS_FAILED,
            'error'  => 'The report could not be generated. Please try again or use narrower filters.',
        ]);
    }

    private function buildCsv(array $filters, string $dir, string $baseName): array
    {
        $fileName = $baseName . '.csv';
        $path = $dir . '/' . $fileName;
        $handle = fopen(Storage::disk('local')->path($path), 'w');

        fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows names correctly
        fputcsv($handle, EventRegistrationReport::SPREADSHEET_HEADINGS, escape: "\\");

        $serial = 0;
        EventRegistrationReport::exportQuery($filters, EventRegistrationReport::SPREADSHEET_RELATIONS)
            ->chunkByIdDesc(self::CHUNK_SIZE, function ($rows) use ($handle, &$serial) {
                foreach ($rows as $row) {
                    fputcsv($handle, EventRegistrationReport::spreadsheetRow($row, ++$serial), escape: "\\");
                }
            });

        fclose($handle);

        return [$path, $fileName];
    }

    private function buildExcel(array $filters, string $dir, string $baseName): array
    {
        $fileName = $baseName . '.xlsx';
        $path = $dir . '/' . $fileName;

        Excel::store(new EventRegistrationQueryExport($filters), $path, 'local');

        return [$path, $fileName];
    }

    private function buildWord(array $filters, string $dir, string $baseName): array
    {
        $fileName = $baseName . '.doc';
        $path = $dir . '/' . $fileName;

        $template = view('admin.event_registrations.export_word', [
            'statusLabels' => EventRegistrationReport::STATUS_LABELS,
        ])->render();
        [$header, $footer] = explode('<!--ROWS-->', $template, 2);

        $handle = fopen(Storage::disk('local')->path($path), 'w');
        fwrite($handle, $header);

        $offset = 0;
        EventRegistrationReport::exportQuery($filters, EventRegistrationReport::DOCUMENT_RELATIONS)
            ->chunkByIdDesc(self::CHUNK_SIZE, function ($rows) use ($handle, &$offset) {
                fwrite($handle, view('admin.event_registrations.export_word_rows', [
                    'registrations' => $rows->values(),
                    'statusLabels'  => EventRegistrationReport::STATUS_LABELS,
                    'offset'        => $offset,
                ])->render());
                $offset += $rows->count();
            });

        if ($offset === 0) {
            fwrite($handle, '<tr><td colspan="8" style="text-align:center;">No records found</td></tr>');
        }

        fwrite($handle, $footer);
        fclose($handle);

        return [$path, $fileName];
    }

    private function buildPdf(array $filters, string $dir, string $baseName, int $total): array
    {
        $disk = Storage::disk('local');
        $partCount = max(1, (int) ceil($total / self::CHUNK_SIZE));
        $parts = [];
        $offset = 0;

        $renderPart = function ($rows) use ($disk, $dir, $baseName, $partCount, &$parts, &$offset) {
            $partNumber = count($parts) + 1;
            $partName = $partCount > 1
                ? sprintf('%s-part-%02d.pdf', $baseName, $partNumber)
                : $baseName . '.pdf';

            $pdf = Pdf::loadView('admin.event_registrations.export_pdf', [
                'registrations' => $rows->values(),
                'statusLabels'  => EventRegistrationReport::STATUS_LABELS,
                'offset'        => $offset,
                'partLabel'     => $partCount > 1 ? "Part {$partNumber} of {$partCount}" : null,
            ]);

            $disk->put($dir . '/' . $partName, $pdf->output());
            $parts[] = $partName;
            $offset += $rows->count();

            unset($pdf);
            gc_collect_cycles();
        };

        EventRegistrationReport::exportQuery($filters, EventRegistrationReport::DOCUMENT_RELATIONS)
            ->chunkByIdDesc(self::CHUNK_SIZE, $renderPart);

        if (empty($parts)) {
            $renderPart(collect()); // still deliver a PDF that says there are no rows
        }

        if (count($parts) === 1) {
            return [$dir . '/' . $parts[0], $parts[0]];
        }

        $zipName = $baseName . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($disk->path($dir . '/' . $zipName), ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create ZIP archive for PDF parts.');
        }
        foreach ($parts as $part) {
            $zip->addFile($disk->path($dir . '/' . $part), $part);
        }
        $zip->close();

        foreach ($parts as $part) {
            File::delete($disk->path($dir . '/' . $part));
        }

        return [$dir . '/' . $zipName, $zipName];
    }
}
