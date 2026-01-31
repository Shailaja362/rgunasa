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
    <!-- ================= HEADER ================= -->
    <div class="header">
        <h1>Event Participation Report</h1>
        <p>Attendance & Feedback Summary</p>
    </div>

    <!-- ================= STUDENT INFO ================= -->
    <div class="section">
        <div class="section-title">Student Information</div>
        <table>
            <tr>
                <td><strong>Name</strong></td>
                <td>{{ $student->name }}</td>
            </tr>
            <tr>
                <td><strong>Student ID</strong></td>
                <td>{{ $student->id }}</td>
            </tr>
        </table>
    </div>

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
                <td>{{ $event->stat_time ?? '' }} - {{ $event->end_time ?? '' }}</td>
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
                <div class="image-box">
                    <img src="{{ public_path('storage/' . $proof->file_path) }}">
                </div>
            @endforeach
        </div>

    </div>

    <!-- ================= FEEDBACK ================= -->
    <div class="section">
        <div class="section-title">Event Feedback</div>

        @php $ratings = json_decode($feedback->ratings, true); @endphp

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

    <!-- ================= COMMENTS ================= -->
    <div class="section">
        <div class="section-title">Additional Comments</div>
        <p>{{ $feedback->comments ?? '— No comments provided —' }}</p>
    </div>

    <!-- ================= FOOTER ================= -->
    <div class="footer">
        Generated on {{ now()->format('d M Y, h:i A') }}
    </div>

</body>

</html>
