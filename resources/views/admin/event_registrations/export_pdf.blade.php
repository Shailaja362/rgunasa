<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h2 { text-align: center; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 6px; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
<img src="{{ public_path('images/rtc_logo.png') }}" style="width:100%; margin-bottom:10px;">
<h2>Event Registration Report</h2>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Student</th>
            <th>Batch</th>
            <th>Semester</th>
            <th>Email</th>
            <th>Event</th>
            <th>Status</th>
            <th>Registered Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($registrations as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $row->student->name ?? '' }}</td>
                <td>{{ $row->student->batch ?? '-' }}</td>
                <td>{{ $row->student->semester ?? '-' }}</td>
                <td>{{ $row->student->email ?? '' }}</td>
                <td>{{ $row->event->title ?? '' }}</td>
                <td>{{ $statusLabels[$row->status] ?? 'Unknown' }}</td>
                <td>{{ $row->created_at->format('d-m-Y') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
