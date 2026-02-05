<?php

namespace App\Http\Controllers\admin;

use App\Models\Event;
use App\Models\Student;
use App\Models\EventReport;
use App\Helpers\ActivityLog;
use Illuminate\Http\Request;
use App\Models\StudentFeedback;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\EventReportImage;
use App\Models\StudentAttendance;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\EventSchedule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\StudentEventRegistration;
use App\Traits\ResolvesEventSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminReportsController extends Controller
{
    use ResolvesEventSchedule;

    public function index(Request $request)
    {
        $adminId = Auth::guard('admin')->id();
        if (!empty(session()->get('super_admin'))) {
            $this->data['reports'] = EventReport::with('get_event_image', 'get_event.get_task', 'get_department')->paginate(10);
        } else {
            $this->data['reports'] = EventReport::with('get_event_image', 'get_event.get_task', 'get_department')
                ->where('created_by', $adminId)->paginate(10);
        }
        return view('admin.admin_reports_index')->with($this->data);
    }

    public function crearteReport(Request $request)
    {
        if ($request->ajax()) {
            if ($request->get_event_date) {
                $get_event = Event::where('id', $request->eventId)->first();
                return response()->json([
                    'success' => true,
                    'event' => $get_event
                ]);
            }
        }
        $adminId = Auth::guard('admin')->id();
        $this->data['event'] = Event::where('created_by', $adminId)->get();
        $this->data['departments'] = Department::get();
        return view('admin.create_admin_report')->with($this->data);
    }

    public function saveReport(Request $request)
    {
        $request->validate([
            'event_id'        => 'required|exists:events,id',
            'department_id'   => 'required',
            'event_date'      => 'required|date',
            'outcome_results' => 'required',
            'feedback_summary' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $schedule = $this->resolveSchedule(
                $request->event_id,
                $request->department_id,
                $request->event_date
            );
            if (!$schedule) {
                throw new \Exception('Schedule not found');
            }
            $report = EventReport::updateOrCreate(
                [
                    'event_id' => $request->event_id,
                    'event_schedule_id' => $schedule->id
                ],
                [
                    'department_id' => $request->department_id,
                    'event_date' => $request->event_date,
                    'outcomes' => $request->outcome_results,
                    'feedback_summary' => $request->feedback_summary,
                    'created_by' => auth('admin')->id()
                ]
            );

            $get_exists_image = EventReportImage::where('report_id', $report->id)->get();
            foreach ($get_exists_image as $img) {
                if (Storage::disk('public')->exists($img->file_path)) {
                    Storage::disk('public')->delete($img->file_path);
                    $img->delete();
                }
            }

            EventReportImage::where('report_id', $report->id)->delete();
            // MULTIPLE PROOF IMAGES
            if ($request->hasFile('proof')) {
                foreach ($request->proof as $file) {
                    $imageName = \Illuminate\Support\Str::uuid() . '.' . $file->extension();
                    $path = $file->storeAs('report_images', $imageName, 'public');
                    $reportimage = new EventReportImage();
                    $reportimage->report_id = $report->id;
                    $reportimage->file_name = $imageName;
                    $reportimage->file_path = $path;
                    $reportimage->file_type = $file->getClientOriginalExtension();
                    $reportimage->save();
                }
            }

            $report->load('get_event');
            $eventTitle = $report->get_event->title ?? 'Unknown Event';
            $user = auth('admin')->user();
            if (!empty($request['report_id'])) {
                ActivityLog::add("{$user->name} - {$eventTitle} - Report Updated", auth('admin')->user());
            } else {
                ActivityLog::add("{$user->name} - {$eventTitle} - New Report Created", auth('admin')->user());
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Event report saved successfully'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Event Report Save Failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function viewPdf($id)
    {
        $report = EventReport::with([
            'get_event.get_task',
            'get_event_image',
            'creator',
            'student_uploads',
            'schedule.department'
        ])->findOrFail($id);

        $scheduleId = $report->event_schedule_id;
        $eventId    = $report->event_id;
        $attendedStudents = StudentAttendance::with([
            'student.get_department',
            'grades' => function ($q) use ($eventId) {
                $q->where('event_id', $eventId);
            }
        ])
            ->where('event_id', $eventId)
            ->where('event_schedule_id', $scheduleId)
            ->whereNotNull('entry_time')
            ->whereNotNull('exit_time')
            ->get();
        $feedbacks = StudentFeedback::with('student')
            ->where('event_id', $eventId)
            ->where('event_schedule_id', $scheduleId)
            ->get();

        $singleFeedback = $feedbacks->last();

        $totals = [];
        $counts = [];

        foreach ($feedbacks as $feedback) {
            $ratings = $this->normalizeRatings($feedback->ratings);
            if (!is_array($ratings)) {
                continue;
            }

            foreach ($ratings as $key => $value) {
                if (!isset($totals[$key])) {
                    $totals[$key] = 0;
                    $counts[$key] = 0;
                }
                if (is_numeric($value)) {
                    $totals[$key] += (int) $value;
                    $counts[$key]++;
                }
            }
        }

        // calculate averages dynamically
        $avgRatings = [];

        foreach ($totals as $key => $total) {
            $avgRatings[$key] = $counts[$key] > 0
                ? round($total / $counts[$key], 1)
                : 0;
        }


        $registeredCount = StudentEventRegistration::where([
            'event_id' => $eventId,
            'event_schedule_id' => $scheduleId
        ])->count();

        $attendedCount = $attendedStudents->count();
        $male_count = StudentAttendance::with([
            'student.get_department',
            'grades' => function ($q) use ($eventId) {
                $q->where('event_id', $eventId);
            }
        ])->whereHas('student', function ($query) {
            $query->where('gender', 'm');
        })
            ->where('event_id', $eventId)
            ->where('event_schedule_id', $scheduleId)
            ->whereNotNull('entry_time')
            ->whereNotNull('exit_time')
            ->get();
        $female_count = StudentAttendance::with([
            'student.get_department',
            'grades' => function ($q) use ($eventId) {
                $q->where('event_id', $eventId);
            }
        ])->whereHas('student', function ($query) {
            $query->where('gender', 'f');
        })
            ->where('event_id', $eventId)
            ->where('event_schedule_id', $scheduleId)
            ->whereNotNull('entry_time')
            ->whereNotNull('exit_time')
            ->get();
        $genderChartUrl = 'https://quickchart.io/chart?c=' . urlencode(json_encode([
            'type' => 'pie',
            'data' => [
                'labels' => ['Male', 'Female'],
                'datasets' => [[
                    'data' => [$male_count->count(), $female_count->count()],
                    'backgroundColor' => ['#7A1C73', '#C36BCB']
                ]]
            ]
        ]));

        $data = [
            'report' => (object)[
                'get_event' => $report->get_event,
                'schedule' => $report->schedule,
                'feedback' => $singleFeedback,
                'avgRatings' => $avgRatings,
                'male_count' => $report->male_count,
                'female_count' => $report->female_count,
                'registered_count' => $registeredCount,
                'attended_count' => $attendedCount,
                'geo_images' => $report->get_event_image,
                'student_uploads' => $report->student_uploads,
                'event_image' => $report->image,
                'outcomes' => $report->outcomes,
                'feedback_summary' => $report->feedback_summary,
                'creator' => $report->creator,
            ],
            'genderChartUrl' => $genderChartUrl,
            'attended_students' => $attendedStudents
        ];

        $pdf = Pdf::loadView('report.pdf.report_template', compact('data'))
            ->setPaper('a4', 'portrait');

        ActivityLog::add(
            auth('admin')->user()->name . ' - ' . $report->get_event->title . ' - Report Viewed',
            auth('admin')->user()
        );

        return $pdf->stream((($report->schedule?->department?->name) ?? '') . ' - ' . $report->get_event->title . ".pdf");
    }

    public function downloadPdf($id)
    {
        $report = EventReport::with([
            'get_event.get_task',
            'get_event_image',
            'creator',
            'student_uploads',
            'schedule.department'
        ])->findOrFail($id);

        $scheduleId = $report->event_schedule_id;
        $eventId    = $report->event_id;
        $attendedStudents = StudentAttendance::with([
            'student.get_department',
            'grades' => function ($q) use ($eventId) {
                $q->where('event_id', $eventId);
            }
        ])
            ->where('event_id', $eventId)
            ->where('event_schedule_id', $scheduleId)
            ->whereNotNull('entry_time')
            ->whereNotNull('exit_time')
            ->get();
        $feedbacks = StudentFeedback::with('student')
            ->where('event_id', $eventId)
            ->where('event_schedule_id', $scheduleId)
            ->get();

        $singleFeedback = $feedbacks->last();

        $totals = [];
        $counts = [];

        foreach ($feedbacks as $feedback) {
            $ratings = $this->normalizeRatings($feedback->ratings);
            if (!is_array($ratings)) {
                continue;
            }

            foreach ($ratings as $key => $value) {
                if (!isset($totals[$key])) {
                    $totals[$key] = 0;
                    $counts[$key] = 0;
                }
                if (is_numeric($value)) {
                    $totals[$key] += (int) $value;
                    $counts[$key]++;
                }
            }
        }

        // calculate averages dynamically
        $avgRatings = [];

        foreach ($totals as $key => $total) {
            $avgRatings[$key] = $counts[$key] > 0
                ? round($total / $counts[$key], 1)
                : 0;
        }


        $registeredCount = StudentEventRegistration::where([
            'event_id' => $eventId,
            'event_schedule_id' => $scheduleId
        ])->count();

        $attendedCount = $attendedStudents->count();
        $male_count = StudentAttendance::with([
            'student.get_department',
            'grades' => function ($q) use ($eventId) {
                $q->where('event_id', $eventId);
            }
        ])->whereHas('student', function ($query) {
            $query->where('gender', 'm');
        })
            ->where('event_id', $eventId)
            ->where('event_schedule_id', $scheduleId)
            ->whereNotNull('entry_time')
            ->whereNotNull('exit_time')
            ->get();
        $female_count = StudentAttendance::with([
            'student.get_department',
            'grades' => function ($q) use ($eventId) {
                $q->where('event_id', $eventId);
            }
        ])->whereHas('student', function ($query) {
            $query->where('gender', 'f');
        })
            ->where('event_id', $eventId)
            ->where('event_schedule_id', $scheduleId)
            ->whereNotNull('entry_time')
            ->whereNotNull('exit_time')
            ->get();
        $genderChartUrl = 'https://quickchart.io/chart?c=' . urlencode(json_encode([
            'type' => 'pie',
            'data' => [
                'labels' => ['Male', 'Female'],
                'datasets' => [[
                    'data' => [$male_count->count(), $female_count->count()],
                    'backgroundColor' => ['#7A1C73', '#C36BCB']
                ]]
            ]
        ]));

        // Prepare PDF data
        $data = [
            'report' => (object)[
                'get_event' => $report->get_event,
                'schedule' => $report->schedule,
                'feedback' => $singleFeedback,
                'avgRatings' => $avgRatings,
                'male_count' => $report->male_count,
                'female_count' => $report->female_count,
                'registered_count' => $registeredCount,
                'attended_count' => $attendedCount,
                'geo_images' => $report->get_event_image,
                'student_uploads' => $report->student_uploads,
                'event_image' => $report->image,
                'outcomes' => $report->outcomes,
                'feedback_summary' => $report->feedback_summary
            ],
            'genderChartUrl' => $genderChartUrl,
            'attended_students' => $attendedStudents
        ];

        $user = auth('admin')->user();
        ActivityLog::add($user->name . ' - ' .  $report->get_event->title . 'Report Downloaded', $user);
        $pdf = Pdf::loadView('report.pdf.report_template', compact('data'))
            ->setPaper('a4', 'portrait');
        return $pdf->download((($report->schedule?->department?->name) ?? '') . ' - ' . $report->get_event->title . ".pdf");
    }

    private function normalizeRatings($value)
    {
        if (empty($value) || $value === 'null') {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }
}
