<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Delete queued report files (and their records) after 24 hours.
\Illuminate\Support\Facades\Schedule::call(function () {
    \App\Models\ReportExport::where('created_at', '<', now()->subDay())
        ->each(function (\App\Models\ReportExport $export) {
            \Illuminate\Support\Facades\Storage::disk('local')->deleteDirectory('exports/registrations/' . $export->id);
            $export->delete();
        });
})->hourly()->name('prune-report-exports')->withoutOverlapping();
