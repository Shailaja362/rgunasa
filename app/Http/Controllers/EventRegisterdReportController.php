<?php

namespace App\Http\Controllers;

use App\Exports\EventRegistrationExport;
use App\Models\Event;
use Illuminate\Http\Request;
use App\Models\StudentEventRegistration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class EventRegisterdReportController extends Controller
{
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

        $this->data['statusLabels'] = [
            1 => 'Registered',
            2 => 'Approved',
            3 => 'Completed',
            4 => 'Cancelled',
        ];

        $this->data['statusClasses'] = [
            1 => 'bg-yellow-100 text-yellow-700',
            2 => 'bg-blue-100 text-blue-700',
            3 => 'bg-green-100 text-green-700',
            4 => 'bg-red-100 text-red-700',
        ];

        if (!empty($request->all())) {
            $query = StudentEventRegistration::with([
                'event',
                'student',
                'student.get_department',
                'get_event_schedule'
            ]);

            $this->data['registrations'] = $this->applyRegistrationReportFilters($query, $request)
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

        return view('admin.registered_event_report_index')->with($this->data);
    }

    public function export(Request $request)
    {
        $this->data['statusLabels'] = [
            1 => 'Registered',
            2 => 'Approved',
            3 => 'Completed',
            4 => 'Cancelled',
        ];

        $query = StudentEventRegistration::with([
            'event',
            'student',
            'student.get_department',
            'get_event_schedule'
        ])
            ->whereHas('event', function ($query) {
                $query->where('publish', 1)
                    ->where('is_active', 'y');
            });

        $this->data['registrations'] = $this->applyRegistrationReportFilters($query, $request)
            ->latest()
            ->get();

        $filters = $request->only([
            'event_id',
            'status',
            'from_date',
            'to_date',
            'search',
            'batch',
            'semester'
        ]);

        if ($request->type === 'word') {
            return response()
                ->view('admin.event_registrations.export_word', $this->data)
                ->header('Content-Type', 'application/msword')
                ->header('Content-Disposition', 'attachment; filename="event-registrations.doc"');
        }

        if ($request->type === 'excel') {
            return Excel::download(
                new EventRegistrationExport([
                    'registrations' => collect($this->data['registrations'] ?? []),
                    'statusLabels'  => $this->data['statusLabels'] ?? [],
                ]),
                'event-registrations.xlsx'
            );
        }

        if ($request->type === 'csv') {
            return Excel::download(
                new EventRegistrationExport([
                    'registrations' => collect($this->data['registrations'] ?? []),
                    'statusLabels'  => $this->data['statusLabels'] ?? [],
                ]),
                'event-registrations.csv'
            );
        }

        if ($request->type === 'pdf') {
            $pdf = Pdf::loadView(
                'admin.event_registrations.export_pdf',
                $this->data
            );

            return $pdf->download('event-registrations.pdf');
        }
    }

    private function applyRegistrationReportFilters($query, Request $request)
    {
        $query->when($request->event_id, fn($q) => $q->where('event_id', $request->event_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->from_date, function ($q) use ($request) {
                $q->whereHas('get_event_schedule', function ($schedule) use ($request) {
                    $schedule->whereDate('event_date', 'like', '%' . $request->from_date . '%');
                });
            })
            ->when($request->to_date, function ($q) use ($request) {
                $q->whereHas('get_event_schedule', function ($schedule) use ($request) {
                    $schedule->whereDate('event_date', 'like', '%' . $request->to_date . '%');
                });
            })
            ->when($request->search, function ($q) use ($request) {
                $q->whereHas('student', function ($student) use ($request) {
                    $student->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            });

        $query->when($request->filled('semester') || $request->filled('batch'), function ($q) use ($request) {
            $semester = $request->semester;
            $batch = $request->batch;
            $isFirstYearFilter = in_array((int) $semester, [1, 2]);

            $q->whereHas('student', function ($studentQ) use ($semester, $batch) {
                if (!empty($semester)) {
                    $studentQ->where('semester', $semester);
                }

                if (!empty($batch)) {
                    $studentQ->where('batch', $batch);
                }
            });

            $q->whereHas('get_event_schedule', function ($scheduleQ) use ($semester, $batch, $isFirstYearFilter) {
                $scheduleQ->where(function ($mainQ) use ($semester, $batch, $isFirstYearFilter) {
                    $mainQ->where(function ($normalQ) use ($semester, $batch) {
                        if (!empty($semester)) {
                            $normalQ->where('semester', $semester);
                        }
                        if (!empty($batch)) {
                            $normalQ->where('batch', $batch);
                        }
                    });

                    if ($isFirstYearFilter) {
                        $mainQ->orWhere(function ($commonQ) use ($batch) {
                            $commonQ->whereNull('programme_id')
                                ->whereNull('section')
                                ->whereNull('semester')
                                ->where(function ($batchQ) use ($batch) {
                                    $batchQ->whereNull('batch');
                                    if (!empty($batch)) {
                                        $batchQ->orWhere('batch', $batch);
                                    }
                                });
                        });
                    }
                });
            });
        });

        return $query;
    }
}
