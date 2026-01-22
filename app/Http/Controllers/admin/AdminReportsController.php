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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\StudentEventRegistration;

class AdminReportsController extends Controller
{
    public function index(Request $request)
    {
        $this->data['reports'] = EventReport::with('get_event_image', 'get_event.get_task')->get();
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

        return view('admin.create_admin_report')->with($this->data);
    }

    public function saveReport(Request $request)
    {
        try {
            $rules = [
                'event_id'  => 'required',
                'proof.*'    => 'required|mimes:jpg,jpeg,png',
                'male_count'   => 'required|numeric',
                'female_count'   => 'required|numeric',
                'outcome_results' => 'required',
                'feedback_summary' => 'required',
                'certificates' => 'required',
                'attendance_in' => 'required',
                'attendance_out' => 'required',
            ];
            $request->validate($rules);

            if (!empty($request->report_id)) {
                $report = EventReport::findOrFail($request->report_id);
                $message = "Report Updated Successfully";
            } else {
                $report = new EventReport();
                $message = "Report Created Successfully";
            }

            // SINGLE FILE UPLOADS
            $cer_path = $request->hasFile('certificates')
                ? $request->certificates->storeAs(
                    'report_certificate',
                    time() . '_' . uniqid() . '.' . $request->certificates->extension(),
                    'public'
                )
                : $report->certificates;

            $attendance_in_path = $request->hasFile('attendance_in')
                ? $request->attendance_in->storeAs(
                    'report_attendance_in',
                    time() . '_' . uniqid() . '.' . $request->attendance_in->extension(),
                    'public'
                )
                : $report->attendance_in;

            $attendance_out_path = $request->hasFile('attendance_out')
                ? $request->attendance_out->storeAs(
                    'report_attendance_out',
                    time() . '_' . uniqid() . '.' . $request->attendance_out->extension(),
                    'public'
                )
                : $report->attendance_out;
            $user = auth('admin')->user();
            $exist_report = EventReport::where(['event_id' => $request->event_id, 'created_by' => $user->id])->exists();

            if ($exist_report) {
                return response()->json([
                    'success' => false,
                    'message' => 'This event report already exists!'
                ], 500);
            }
            // SAVE MAIN REPORT
            $report->event_id = $request->event_id;
            $report->created_by = $user->id;
            $report->male_count = $request->male_count;
            $report->female_count = $request->female_count;
            $report->outcomes = $request->outcome_results;
            $report->feedback_summary = $request->feedback_summary;
            $report->certificates = $cer_path;
            $report->attendance_in = $attendance_in_path;
            $report->attendance_out = $attendance_out_path;
            $report->save();

            // DELETE REMOVED IMAGES
            if (!empty($request->removed_images)) {
                $ids = json_decode($request->removed_images);
                foreach ($ids as $id) {
                    $img = EventReportImage::find($id);
                    if ($img && Storage::disk('public')->exists($img->file_path)) {
                        Storage::disk('public')->delete($img->file_path);
                        $img->delete();
                    }
                }
            }

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

            // Load event relation for logging
            $report->load('get_event');
            $eventTitle = $report->get_event->title ?? 'Unknown Event';

            if (!empty($request['report_id'])) {
                ActivityLog::add("{$user->name} - {$eventTitle} - Report Updated", auth('admin')->user());
            } else {
                ActivityLog::add("{$user->name} - {$eventTitle} - New Report Created", auth('admin')->user());
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function viewPdf($id)
    {
        $event = EventReport::with(['get_event.get_task', 'get_event_image', 'creator', 'student_uploads'])->findOrFail($id);
        $attended_students = StudentAttendance::with(['student', 'student.get_department', 'get_grade',  'grades' => function ($q) use ($event) {
            $q->where('event_id', $event->event_id);
        }])
            ->whereHas('grades', function ($query) use ($event) {
                $query->where('event_id', $event->event_id);
            })
            ->whereNotNull('entry_time')
            ->whereNotNull('exit_time')
            ->where('event_id', $event->event_id)
            ->get();
        $singleFeedback = StudentFeedback::with([
            'student',
            'uploads' => function ($q) use ($event) {
                $q->where('event_id', $event->event_id);
            }
        ])
            ->where('event_id', $event->event_id)
            ->latest()
            ->first();
        // Calculate average ratings
        $feedbacks = StudentFeedback::with('student')
            ->where('event_id', $event->event_id)->get();

        $keys = [
            'overall_experience',
            'engagement',
            'organization',
            'coordination',
            'recommendation',
        ];

        $totals = array_fill_keys($keys, 0);
        $counts = array_fill_keys($keys, 0);

        foreach ($feedbacks as $feedback) {

            // normalize here
            $ratings = $this->normalizeRatings($feedback->ratings);

            foreach ($keys as $key) {
                if (isset($ratings[$key]) && is_numeric($ratings[$key])) {
                    $totals[$key] += (int) $ratings[$key];
                    $counts[$key]++;
                }
            }
        }

        // Final averages
        $avgRatings = [];
        foreach ($keys as $key) {
            $avgRatings[$key] = $counts[$key] > 0
                ? round($totals[$key] / $counts[$key], 1)
                : 0;
        }


        // Gender counts
        $studentIds = $feedbacks->pluck('student_id')->toArray();

        // Prepare gender chart via QuickChart.io
        $genderChartUrl = 'https://quickchart.io/chart?c=' . urlencode(json_encode([
            'type' => 'pie',
            'data' => [
                'labels' => ['Male', 'Female'],
                'datasets' => [[
                    'data' => [$event->male_count, $event->female_count],
                    'backgroundColor' => ['#7A1C73', '#C36BCB']
                ]]
            ],
            'options' => ['plugins' => ['legend' => ['position' => 'bottom']]]
        ]));
        // Counts
        $registeredCount = StudentEventRegistration::where('event_id', $event->event_id)->count();
        $attendedCount   =  StudentAttendance::where('event_id', $event->event_id)
                            ->whereNotNull('entry_time')
                            ->whereNotNull('exit_time')
                            ->count();

        // Prepare PDF data
        $data = [
            'report' => (object)[
                'get_event' => $event->get_event,
                'feedback' =>  $singleFeedback,
                'avgRatings' => $avgRatings,
                'male_count' => $event->male_count,
                'female_count' => $event->female_count,
                'registered_count' => $registeredCount,
                'attended_count' => $attendedCount,
                'geo_images' => $event->get_event_image,
                'student_uploads' => $event->student_uploads,
                'event_image' => $event->image, // optional main event image
                'outcomes' => $event->outcomes,
                'feedback_summary' => $event->feedback_summary
            ],
            'genderChartUrl' => $genderChartUrl,
            'attended_students' =>  $attended_students
        ];

        // Generate PDF
        $pdf = Pdf::loadView('report.pdf.report_template', compact('data'))
            ->setPaper('a4', 'portrait');
        $user = auth('admin')->user();
        ActivityLog::add($user->name  . ' - ' . $event->get_event->title . ' - Report Viewed', $user);
        return $pdf->stream("event_report_{$event->get_event->title}.pdf");
    }

    public function downloadPdf($id)
    {
        $event = EventReport::with(['get_event.get_task', 'get_event_image', 'creator', 'student_uploads'])->findOrFail($id);
        // Get feedbacks
        $attended_students = StudentAttendance::with(['student', 'student.get_department', 'get_grade',  'grades' => function ($q) use ($event) {
            $q->where('event_id', $event->event_id);
        }])
            ->whereHas('grades', function ($query) use ($event) {
                $query->where('event_id', $event->event_id);
            })
            ->whereNotNull('entry_time')
            ->whereNotNull('exit_time')
            ->where('event_id', $event->event_id)
            ->get();

        $singleFeedback = StudentFeedback::with([
            'student',
            'uploads' => function ($q) use ($event) {
                $q->where('event_id', $event->event_id);
            }
        ])
            ->where('event_id', $event->event_id)
            ->latest()
            ->first();
        $feedbacks = StudentFeedback::with('student')
            ->where('event_id', $event->event_id)->get();

        $keys = [
            'overall_experience',
            'engagement',
            'organization',
            'coordination',
            'recommendation',
        ];

        $totals = array_fill_keys($keys, 0);
        $counts = array_fill_keys($keys, 0);

        foreach ($feedbacks as $feedback) {

            // normalize here
            $ratings = $this->normalizeRatings($feedback->ratings);

            foreach ($keys as $key) {
                if (isset($ratings[$key]) && is_numeric($ratings[$key])) {
                    $totals[$key] += (int) $ratings[$key];
                    $counts[$key]++;
                }
            }
        }

        // Final averages
        $avgRatings = [];
        foreach ($keys as $key) {
            $avgRatings[$key] = $counts[$key] > 0
                ? round($totals[$key] / $counts[$key], 1)
                : 0;
        }

        // Gender counts
        $studentIds = $feedbacks->pluck('student_id')->toArray();
        // Prepare gender chart via QuickChart.io
        $genderChartUrl = 'https://quickchart.io/chart?c=' . urlencode(json_encode([
            'type' => 'pie',
            'data' => [
                'labels' => ['Male', 'Female'],
                'datasets' => [[
                    'data' => [$event->male_count, $event->female_count],
                    'backgroundColor' => ['#7A1C73', '#C36BCB']
                ]]
            ],
            'options' => ['plugins' => ['legend' => ['position' => 'bottom']]]
        ]));
        // Counts
        $registeredCount = StudentEventRegistration::where('event_id', $event->event_id)->count();
        $attendedCount   =  StudentAttendance::where('event_id', $event->event_id)
            ->whereNotNull('entry_time')
            ->whereNotNull('exit_time')
            ->count();

        // Prepare PDF data
        $data = [
            'report' => (object)[
                'get_event' => $event->get_event,
                'feedback' => $singleFeedback,
                'avgRatings' => $avgRatings,
                'male_count' => $event->male_count,
                'female_count' => $event->female_count,
                'registered_count' => $registeredCount,
                'attended_count' => $attendedCount,
                'geo_images' => $event->get_event_image,
                'student_uploads' => $event->student_uploads,
                'event_image' => $event->image,
                'outcomes' => $event->outcomes,
                'feedback_summary' => $event->feedback_summary
            ],
            'genderChartUrl' => $genderChartUrl,
            'attended_students' => $attended_students
        ];

        $user = auth('admin')->user();
        ActivityLog::add($user->name . ' - ' .  $event->get_event->title . 'Report Downloaded', $user);
        $pdf = Pdf::loadView('report.pdf.report_template', compact('data'))
            ->setPaper('a4', 'portrait');
        return $pdf->download("event_report_{$event->get_event->title}.pdf");
    }

    private function normalizeRatings($value)
    {
        if (empty($value) || $value === 'null') {
            return [];
        }

        // If it's already an array, return as-is
        if (is_array($value)) {
            return $value;
        }

        // Only decode if it's a string
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            // Sometimes JSON is double-encoded
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }

            return is_array($decoded) ? $decoded : [];
        }

        // For any other type, return empty array
        return [];
    }
}
