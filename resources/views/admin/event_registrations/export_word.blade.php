<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Event Registration Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
<img src="{{ public_path('images/rgu_logo.jpeg') }}" style="width:100%; margin-bottom:10px;">
<h2>Event Registration Report</h2>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Student Name</th>
            <th>Batch</th>
            <th>Semester</th>
            <th>Email</th>
            <th>Event</th>
            <th>Status</th>
            <th>Registered Date</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($registrations as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $row->student->name ?? '-' }}</td>
                <td>{{ $row->student->batch ?? '-' }}</td>
                <td>{{ $row->student->semester ?? '-' }}</td>
                <td>{{ $row->student->email ?? '-' }}</td>
                <td>{{ $row->event->title ?? '-' }}</td>
                <td>{{ $statusLabels[$row->status] ?? 'Unknown' }}</td>
                <td>{{ $row->created_at->format('d-m-Y') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align:center;">
                    No records found
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
