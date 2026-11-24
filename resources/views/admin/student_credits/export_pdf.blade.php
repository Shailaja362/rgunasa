<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 20px 15px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            margin: 0;
            padding: 0;
        }

        .logo { width: 60%; margin: 0 auto 6px auto; display: block; }

        h2 { text-align: center; margin: 4px 0 6px 0; font-size: 13px; }

        .meta { text-align: center; font-size: 9px; margin-bottom: 8px; }

        table { width: 100%; border-collapse: collapse; table-layout: fixed; }

        th, td {
            border: 1px solid #000;
            padding: 3px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        th { background: #f0f0f0; text-align: center; font-size: 8px; }
        td { font-size: 7.5px; }
        .c { text-align: center; }
        .pending { color: #b91c1c; font-weight: bold; }
        .completed { color: #15803d; font-weight: bold; }

        /* Programme and semester are in the heading (a PDF is always one
           programme + one semester), so they are not repeated per row. */
        .w-sl { width: 4%; }
        .w-reg { width: 13%; }
        .w-name { width: 17%; }
        .w-sec { width: 5%; }
        .w-batch { width: 8%; }
        .w-events { width: 29%; }
        .w-num { width: 6%; }
        .w-status { width: 6%; }
    </style>
</head>
<body>

<img src="{{ public_path('images/rgu_logo.jpeg') }}" class="logo">

<h2>Student Credits Report</h2>
<div class="meta">
    Semester: <b>{{ $filters['semester'] }}</b>
    &nbsp;|&nbsp; Programme: <b>{{ $programmeName ?? 'All Programmes' }}</b>
    @if (!empty($filters['section'])) &nbsp;|&nbsp; Section: <b>{{ strtoupper($filters['section']) }}</b> @endif
    @if (!empty($filters['batch'])) &nbsp;|&nbsp; Batch: <b>{{ $filters['batch'] }}</b> @endif
    &nbsp;|&nbsp; Required Credits: <b>{{ $required === null ? 'Not set' : \App\Services\Reports\StudentCreditReport::number($required) }}</b>
    &nbsp;|&nbsp; Students: <b>{{ $rows->count() }}</b>
</div>

{{-- One small table per page: DomPDF slows down sharply on a single table
     spanning many pages. 12 rows on the first page (logo + heading), 20 after;
     leaves room for long programme names / event lists that wrap to 2-3 lines. --}}
@php
    $pages = collect([$rows->slice(0, 12)])
        ->merge($rows->slice(12)->chunk(20))
        ->filter(fn ($chunk) => $chunk->isNotEmpty());
@endphp

@forelse ($pages as $chunk)
<table @if (!$loop->last) style="page-break-after: always;" @endif>
    <thead>
        <tr>
            <th class="w-sl">#</th>
            <th class="w-reg">Register No.</th>
            <th class="w-name">Student Name</th>
            <th class="w-sec">Sec</th>
            <th class="w-batch">Batch</th>
            <th class="w-events">Credited Events</th>
            <th class="w-num">Required</th>
            <th class="w-num">Earned</th>
            <th class="w-num">Remaining</th>
            <th class="w-status">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($chunk as $row)
            <tr>
                <td class="c">{{ $row['serial'] }}</td>
                <td>{{ $row['register_number'] }}</td>
                <td>{{ $row['name'] }}</td>
                <td class="c">{{ $row['section'] }}</td>
                <td class="c">{{ $row['batch'] }}</td>
                <td>{{ $row['events'] }}</td>
                <td class="c">{{ $row['required'] }}</td>
                <td class="c">{{ $row['earned'] }}</td>
                <td class="c">{{ $row['remaining'] }}</td>
                <td class="c {{ strtolower($row['status']) }}">{{ $row['status'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@empty
<p style="text-align:center;">No students found for these filters.</p>
@endforelse

</body>
</html>
