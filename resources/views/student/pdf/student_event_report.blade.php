<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Event Participation Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #9D55EC;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            color: #9D55EC;
            margin: 0;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            background: #F2E8F5;
            padding: 8px;
            font-weight: bold;
            border-left: 4px solid #9D55EC;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table td {
            padding: 6px;
            border: 1px solid #ddd;
        }

        .rating {
            color: #f4b400;
            font-size: 14px;
        }

        .images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .image-box {
            width: 48%;
            height: 180px;
            /* SAME HEIGHT FOR ALL */
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
        }

        .image-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            /* IMPORTANT */
        }

        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 40px;
            color: #777;
        }
    </style>
</head>

<body>
    <img src="{{ public_path('images/rtc_logo.png') }}" style="width:100%; margin-bottom:10px;">
    <div class="header">
        <h1>Event Participation Report</h1>
        <p>Attendance & Feedback Summary</p>
    </div>
    <div class="section">
        <div class="section-title">Student Information</div>
        <table>
            <tr>
                <td><strong>Name</strong></td>
                <td>{{ $student->name ?? '' }}</td>
            </tr>
            <tr>
                <td><strong>Register Number</strong></td>
                <td>{{ $student->register_number }}</td>
            </tr>
            <tr>
                <td><strong>Department Name</strong></td>
                <td>{{ $student?->get_department->name ?? '' }}</td>
            </tr>
        </table>
    </div>
    @php
        if ($event_schedule->is_reserve_date == 'y') {
            $start_time = $event->reserve_start_time;
            $end_time = $event->reserve_end_time;
        } else {
            $start_time = $event->start_time;
            $end_time = $event->end_time;
        }
    @endphp
    <!-- ================= EVENT INFO ================= -->
    <div class="section">
        <div class="section-title">Event Details</div>
        <table>
            <tr>
                <td><strong>Event Title</strong></td>
                <td>{{ $event->title }}</td>
            </tr>
            <tr>
                <td><strong>Date</strong></td>
                <td>{{ \Carbon\Carbon::parse($event_schedule->event_date)->format('d M Y') }}</td>
            </tr>
            <tr>
                <td><strong>Time</strong></td>
                <td>
                    {{ $start_time ? \Carbon\Carbon::parse($start_time)->format('h:i A') : '' }}
                    -
                    {{ $end_time ? \Carbon\Carbon::parse($end_time)->format('h:i A') : '' }}
                </td>
            </tr>
            <tr>
                <td><strong>Location</strong></td>
                <td>{{ $event->location }}</td>
            </tr>
            <tr>
                <td><strong>Attendance</strong></td>
                <td><strong style="color:green">Present</strong></td>
            </tr>
        </table>
    </div>

    <!-- ================= PROOF IMAGES ================= -->
    <div class="section">
        <div class="section-title">Uploaded Proof</div>
        <div class="images">
            @foreach ($proofs as $proof)
                @if ($proof->file_type == 'jpg' || $proof->file_type == 'jpeg' || $proof->file_type == 'png')
                    <div class="image-box">
                        <img src="{{ public_path('storage/' . $proof->file_path) }}">
                    </div>
                @else
                    <div class="file-box"
                        style="display: flex; align-items: center; gap: 8px; padding: 8px; border: 1px solid #ccc; border-radius: 6px; max-width: 200px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#555"
                            viewBox="0 0 24 24">
                            <path
                                d="M6 2a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6H6zm7 1.5V9h5.5L13 3.5z" />
                        </svg>
                        <span style="font-size: 14px; color: #333;">{{ $proof->file_name ?? 'Unknown file' }}</span>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    <!-- ================= FEEDBACK ================= -->
    <div class="section">
        <div class="section-title">Event Feedback</div>
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
        <table>
            @foreach ($ratings as $key => $value)
                <tr>
                    <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                    <td class="rating">
                        {{ str_repeat('★', $value) }}
                        {{ str_repeat('☆', 5 - $value) }}
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
    <div class="section">
        <div class="section-title">Key Take Away</div>
        <p>{{ $feedback->comments ?? '— No key take away provided —' }}</p>
    </div>
    <div class="footer">
        Generated on {{ now()->format('d M Y, h:i A') }}
    </div>
</body>

</html>
