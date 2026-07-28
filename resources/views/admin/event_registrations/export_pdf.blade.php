<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 20px 15px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            margin: 0;
            padding: 0;
        }

        h2 {
            text-align: center;
            margin: 8px 0 12px 0;
            font-size: 14px;
        }

        .logo {
            width: 100%;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* important */
        }

        th, td {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        th {
            background: #f0f0f0;
            text-align: center;
            font-size: 9px;
        }

        td {
            font-size: 8px;
        }

        .text-center {
            text-align: center;
        }

        /* Fixed column widths */
        .col-sl      { width: 4%; }
        .col-student { width: 14%; }
        .col-batch   { width: 10%; }
        .col-sem     { width: 6%; }
        .col-email   { width: 20%; }
        .col-event   { width: 24%; }
        .col-status  { width: 10%; }
        .col-date    { width: 12%; }
    </style>
</head>
<body>

<img src="{{ public_path('images/rgu_logo.jpeg') }}" class="logo">

<h2>Event Registration Report</h2>

<table>
    <thead>
        <tr>
            <th class="col-sl">#</th>
            <th class="col-student">Student</th>
            <th class="col-batch">Batch</th>
            <th class="col-sem">Semester</th>
            <th class="col-email">Email</th>
            <th class="col-event">Event</th>
            <th class="col-status">Status</th>
            <th class="col-date">Registered Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($registrations as $i => $row)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $row->student->name ?? '' }}</td>
                <td class="text-center">{{ $row->student->batch ?? '-' }}</td>
                <td class="text-center">{{ $row->student->semester ?? '-' }}</td>
                <td>{{ $row->student->email ?? '' }}</td>
                <td>{{ $row->event->title ?? '' }}</td>
                <td class="text-center">{{ $statusLabels[$row->status] ?? 'Unknown' }}</td>
                <td class="text-center">{{ optional($row->created_at)->format('d-m-Y') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
