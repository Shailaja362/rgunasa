<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Event Report</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            color: #1A1A1A;
            margin: 0;
            padding: 0;
        }

        .container {
            padding: 20px;
        }

        /* HEADER */
        .header {
            border-bottom: 3px solid #7A1C73;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .event-title {
            font-size: 13px;
            font-weight: bold;
            color: #7A1C73;
        }

        .event-meta {
            font-size: 12px;
            text-align: right;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* SECTION */
        .section-title {
            margin-top: 25px;
            margin-bottom: 10px;
            padding: 6px 10px;
            font-weight: bold;
            background: #F1E5F7;
            border-left: 5px solid #7A1C73;
        }

        /* INFO TABLE */
        .info-table td {
            padding: 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        .student_table {
            width: 100%;
            border-collapse: collapse;
            /* ensures borders are not doubled */
        }

        .student_table th,
        .student_table td {
            /* visible border */
            padding: 8px;
            text-align: left;
        }

        .student_table th {
            background-color: #F1E5F7;
        }

        .label {
            width: 30%;
            font-weight: bold;
            background: #FAF7FB;
        }

        /* SUMMARY BOX */
        .summary-box {
            background: #F9F3FB;
            border: 1px solid #ccc;
            border-radius: 6px;
            text-align: center;
            padding: 12px;
        }

        .summary-box h2 {
            margin: 0;
            font-size: 22px;
            color: #7A1C73;
        }

        /* BARS */
        .bar-item {
            margin-bottom: 12px;
        }

        .bar-label {
            font-size: 12px;
            margin-bottom: 4px;
        }

        .bar-bg {
            width: 100%;
            background: #e0e0e0;
            height: 12px;
            border-radius: 6px;
        }

        .bar-fill {
            height: 12px;
            border-radius: 6px;
        }

        /* FEEDBACK */
        .feedback-item {
            border-bottom: 1px solid #ddd;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .stars {
            color: #F1C40F;
            font-size: 16px;
            letter-spacing: 2px;
        }

        /* IMAGES */
        .image-grid img {
            width: 32%;
            height: 140px;
            object-fit: cover;
            border: 1px solid #bbb;
            margin-right: 1%;
            margin-bottom: 10px;
            margin-top: 30px;
        }

        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 11px;
        }
    </style>
</head>

<body>
    @php
        function stars($rating)
        {
            $full = floor($rating);
            return str_repeat('★', $full) . str_repeat('☆', 5 - $full);
        }

        $data['report'] = $data['report'];
        $avg = $data['report']->avgRatings ?? [];
    @endphp

    <div class="container">
        <!-- LOGO -->
        <img src="{{ public_path('images/rgu_logo.jpeg') }}" style="width:100%; margin-bottom:10px;">
        <!-- HEADER -->
        <div class="header">
            <table>
                <tr>
                    <td style="width:60%;">
                        <div class="event-title">
                            {{ $data['report']->get_event->title ?? 'Event Title' }}
                        </div>
                    </td>
                    <td style="width:50%;" class="event-meta">
                        <b>
                            Date:
                            {{ \Carbon\Carbon::parse(optional($data['report']->schedule)->event_date)->format('d M Y') }}
                            <br>
                            Session: {{ $data['report']->get_event->session == 1 ? 'FN' : 'AN' }}
                        </b>
                    </td>
                </tr>
            </table>
        </div>

        <!-- SUMMARY -->
        <table style="margin-top:15px;">
            <tr>
                <td class="summary-box">
                    <h2>{{ $data['report']->registered_count ?? 0 }}</h2>
                    Registered
                </td>
                <td width="20"></td>
                <td class="summary-box">
                    <h2>{{ $data['report']->attended_count ?? 0 }}</h2>
                    Attended
                </td>
            </tr>
        </table>

        <!-- EVENT INFORMATION -->
        <div class="section-title">Event Information</div>
        <table class="info-table">
            <tr>
                <td class="label">Event Date</td>
                <td>{{ \Carbon\Carbon::parse(optional($data['report']->schedule)->event_date)->format('d M Y') }}</td>
            </tr>
            <tr>
                <td class="label">Programme</td>
                <td>{{ $data['report']->schedule?->programme_names ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Section</td>
                <td>{{ $data['report']->schedule?->section_names ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Batch</td>
                <td>{{ optional($data['report']->schedule)->batch ? str_replace(',', ', ', $data['report']->schedule->batch) : 'All Batches' }}</td>
            </tr>
            <tr>
                <td class="label">Semester</td>
                <td>{{ optional($data['report']->schedule)->semester ? str_replace(',', ', ', $data['report']->schedule->semester) : 'All Semesters' }}</td>
            </tr>
            <tr>
                <td class="label">Report Created By</td>
                <td>{{ $data['report']->creator->name ?? '' }}</td>
            </tr>
        </table>

        <!-- OUTCOMES -->
        <div class="section-title">Event Outcomes</div>
        <p>{{ $data['report']->outcomes }}</p>

        <!-- FEEDBACK SUMMARY -->
        <div class="section-title">Feedback Summary</div>
        <p>{{ $data['report']->feedback_summary }}</p>

        <!-- GENDER PARTICIPATION -->
        <div class="section-title">Attended Students Lists</div>
        <table class="info-table">
            <thead>
                <th>S.No</th>
                <th>Register Number</th>
                <th>Student Name</th>
                <th>Programme Name</th>
                <th>Entry Time</th>
                <th>Exit Time</th>
                <th>Grade</th>
            </thead>
            <tbody>
                @foreach ($data['attended_students'] as $student)
                    @php
                        $grade = $student->grades->first();
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $student->student?->register_number ?? '' }}</td>
                        <td>{{ $student->student?->name ?? '' }}</td>
                        <td>{{ $student->student?->get_programme?->name ?? '' }}</td>
                        <td>{{ $student->entry_time ? \Carbon\Carbon::parse($student->entry_time)->format('H:i A') : '' }}
                        </td>
                        <td>{{ $student->exit_time ? \Carbon\Carbon::parse($student->exit_time)->format('H:i A') : '' }}
                        </td>
                        <td>{{ $grade->grade ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <!-- AVERAGE FEEDBACK -->
        <div class="section-title">Average Feedback</div>

        <table class="info-table">

            @forelse ($data['report']->avgRatings as $key => $rating)
                @php
                    $label = ucwords(str_replace('_', ' ', $key));
                @endphp

                <tr>
                    <td class="label">{{ $label }}</td>
                    <td>
                        <span class="stars">{{ stars($rating) }}</span>
                        <b>{{ $rating }}/5</b>
                    </td>
                </tr>

            @empty
                <tr>
                    <td colspan="2">No ratings available</td>
                </tr>
            @endforelse

        </table>

        <!-- STUDENT FEEDBACK -->
     <div class="section-title">Student Feedback</div>

@php
    $f = $data['report']->feedback ?? null;
    $ratings = [];

    if ($f && !empty($f->ratings)) {

        // If already array (because of $casts)
        if (is_array($f->ratings)) {
            $ratings = $f->ratings;

        // If string (normal JSON or double encoded)
        } elseif (is_string($f->ratings)) {

            $decoded = json_decode($f->ratings, true);

            // If double encoded
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }

            $ratings = is_array($decoded) ? $decoded : [];
        }
    }
@endphp

@if ($data['report']->feedback)
    <table class="info-table">
        <tr>
            <td class="label">Student Name</td>
            <td>{{ optional($data['report']->feedback->student)->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Register Number</td>
            <td>{{ optional($data['report']->feedback->student)->register_number ?? '-' }}</td>
        </tr>
        @if ($ratings)
        @foreach ($ratings as $key => $value)
            @php
                $label = ucwords(str_replace('_', ' ', $key));
                $rating = (int) $value;
            @endphp
            <tr>
                <td class="label">{{ $label }}</td>
                <td>
                    <span class="stars">{{ stars($rating) }}</span>
                    <b>{{ $rating }}/5</b>
                </td>
            </tr>
        @endforeach
        @endif
        <tr>
            <td class="label">Key Take Away</td>
            <td>{{ optional($data['report']->feedback)->comments ?? '-' }}</td>
        </tr>
    </table>
@else
    <p>No feedback submitted yet.</p>
@endif

        <!-- GEO IMAGES -->
        @if ($data['report']->geo_images->count())
            <div class="section-title">Geo Tagged Photos</div>
            <div class="image-grid">
                @foreach ($data['report']->geo_images as $img)
                    <img src="{{ public_path('storage/' . $img->file_path) }}">
                @endforeach
            </div>
        @endif
        <div class="footer">
            Generated on {{ now()->format('d M Y h:i A') }}
        </div>
    </div>
</body>

</html>
