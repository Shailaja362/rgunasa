<?php

namespace App\Http\Controllers;

use App\Exports\EventRegistrationExport;
use App\Models\Event;
use Illuminate\Http\Request;
use App\Models\StudentEventRegistration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;

class EventRegisterdReportController extends Controller
{
    public function index(Request $request)
    {
        $this->data['events'] = Event::get();
        $this->data['statusLabels']  = [
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
        // Registrations query
        if(!empty($request->all())){
            $this->data['registrations'] = StudentEventRegistration::with([
                'event:id,title',
                'student:id,name,email,department_id',
                'student.get_department',
                'get_event_schedule'
            ])
                ->when($request->event_id, function ($q) use ($request) {
                    $q->where('event_id', $request->event_id);
                })
                ->when($request->status, function ($q) use ($request) {
                    $q->where('status', $request->status);
                })
                ->when($request->from_date, function ($q) use ($request) {
                    $q->whereHas('get_event_schedule', function ($schedule) use ($request) {
                       $schedule->whereDate('event_date', 'like', '%' . $request->from_date . '%')
                             ->orWhereDate('reserve_date', 'like', '%' . $request->from_date . '%');
                   });
             })
                ->when($request->to_date, function ($q) use ($request) {
                $q->whereHas('get_event_schedule', function ($schedule) use ($request) {
                    $schedule->whereDate('event_date', 'like', '%' . $request->to_date . '%')
                        ->orWhereDate('reserve_date', 'like', '%' . $request->to_date . '%');
                });
                })
                ->when($request->search, function ($q) use ($request) {
                    $q->whereHas('student', function ($student) use ($request) {
                        $student->where('name', 'like', '%' . $request->search . '%')
                            ->orWhere('email', 'like', '%' . $request->search . '%');
                    });
                })
                ->latest()
                ->paginate(10);
            }else{
            $this->data['registrations'] = new LengthAwarePaginator(
                collect(), // empty collection
                0,         // total
                10,        // per page
                1,         // current page
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

        $this->data['registrations'] = StudentEventRegistration::with(['event', 'student'])
            ->when($request->event_id, fn($q) => $q->where('event_id', $request->event_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->get();

        $filters = $request->only(['event_id', 'status', 'from_date', 'to_date', 'search']);

        if ($request->type === 'word') {
            return response()
                ->view('admin.event_registrations.export_word', $this->data)
                ->header('Content-Type', 'application/msword')
                ->header('Content-Disposition', 'attachment; filename="event-registrations.doc"');
        }

        if ($request->type === 'excel') {
            return Excel::download(
                new EventRegistrationExport($filters),
                'event-registrations.xlsx'
            );
        }

        if ($request->type === 'csv') {
            return Excel::download(
                new EventRegistrationExport($filters),
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
}
