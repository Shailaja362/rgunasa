<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateRegistrationReport;
use App\Models\Batch;
use App\Models\Event;
use App\Models\Programme;
use App\Models\ReportExport;
use App\Models\StudentEventRegistration;
use App\Services\Reports\EventRegistrationReport;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EventRegisterdReportController extends Controller
{
    private const EXPORT_TYPES = ['pdf', 'excel', 'csv', 'word'];

    public function index(Request $request)
    {
        $adminId = Auth::guard('admin')->id();

        if (!empty(session()->get('super_admin'))) {
            $this->data['events'] = Event::with('get_club')
                ->where([
                    'publish' => 1,
                    'is_active' => 'y'
                ])
                ->get();
        } else {
            $this->data['events'] = Event::with('get_club')
                ->where('created_by', $adminId)
                ->where([
                    'publish' => 1,
                    'is_active' => 'y'
                ])
                ->get();
        }

        $this->data['statusLabels'] = EventRegistrationReport::STATUS_LABELS;

        $this->data['statusClasses'] = [
            1 => 'bg-yellow-100 text-yellow-700',
            2 => 'bg-blue-100 text-blue-700',
            3 => 'bg-green-100 text-green-700',
            4 => 'bg-red-100 text-red-700',
        ];

        $this->data['batches'] = Batch::orderBy('name')->pluck('name');
        $this->data['programmes'] = Programme::orderBy('name')->get(['id', 'name']);

        if (!empty($request->all())) {
            $query = StudentEventRegistration::with([
                'event',
                'student',
                'student.get_department',
                'get_event_schedule'
            ]);

            $this->data['registrations'] = EventRegistrationReport::applyFilters($query, $request->all())
                ->latest()
                ->paginate(10)
                ->appends($request->all());
        } else {
            $this->data['registrations'] = new LengthAwarePaginator(
                collect(),
                0,
                10,
                1,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        $this->data['recentExports'] = $this->recentExports($adminId);

        return view('admin.registered_event_report_index')->with($this->data);
    }

    /**
     * Queue the report instead of building it during the request, so large
     * reports no longer hit the gateway timeout (504). The page shows its
     * progress and a download link once the job has finished.
     */
    public function export(Request $request)
    {
        $filters = EventRegistrationReport::cleanFilters($request->all());
        $back = route('registered_report_index', $request->except('type'));

        // At least one filter is required before downloading. Enforced here as
        // well as in the page's JS, so a direct URL can't export everything.
        if (empty($filters)) {
            return redirect($back)
                ->with('export_error', 'Please apply at least one filter before downloading the report.');
        }

        if (!in_array($request->type, self::EXPORT_TYPES, true)) {
            return redirect($back)->with('export_error', 'Unknown export format.');
        }

        $adminId = Auth::guard('admin')->id();

        // Don't queue the same report twice while one is still being built.
        $alreadyQueued = ReportExport::where('admin_id', $adminId)
            ->where('report', 'event_registrations')
            ->where('type', $request->type)
            ->whereIn('status', [ReportExport::STATUS_PENDING, ReportExport::STATUS_PROCESSING])
            ->get()
            ->contains(fn($export) => $export->filters == $filters);

        if ($alreadyQueued) {
            return redirect($back)
                ->with('export_success', 'This report is already being prepared. It will appear under "Recent Downloads" when ready.');
        }

        $export = ReportExport::create([
            'admin_id' => $adminId,
            'report'   => 'event_registrations',
            'type'     => $request->type,
            'filters'  => $filters,
            'status'   => ReportExport::STATUS_PENDING,
        ]);

        GenerateRegistrationReport::dispatch($export);

        return redirect($back)
            ->with('export_success', 'Your ' . strtoupper($request->type) . ' report is being prepared. '
                . 'It will appear under "Recent Downloads" when ready.');
    }

    /** Status of this admin's recent exports, polled by the report page. */
    public function exportStatus()
    {
        return response()->json([
            'exports' => $this->recentExports(Auth::guard('admin')->id()),
        ]);
    }

    public function downloadExport(ReportExport $export)
    {
        abort_unless((int) $export->admin_id === (int) Auth::guard('admin')->id(), 403);
        abort_unless($export->status === ReportExport::STATUS_COMPLETED, 404);
        abort_unless($export->file_path && Storage::disk('local')->exists($export->file_path), 404, 'This file has expired. Please export it again.');

        return Storage::disk('local')->download($export->file_path, $export->file_name);
    }

    private function recentExports(?int $adminId): array
    {
        if (!$adminId) {
            return [];
        }

        return ReportExport::where('admin_id', $adminId)
            ->where('report', 'event_registrations')
            ->where('created_at', '>=', now()->subDay())
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn(ReportExport $export) => [
                'id'           => $export->id,
                'type'         => strtoupper($export->type),
                'status'       => $export->status,
                'total_rows'   => $export->total_rows,
                'file_name'    => $export->file_name,
                'error'        => $export->error,
                'requested_at' => $export->created_at->format('d-m-Y h:i A'),
                'download_url' => $export->status === ReportExport::STATUS_COMPLETED
                    ? route('admin.event-registrations.exports.download', $export)
                    : null,
            ])
            ->all();
    }
}
