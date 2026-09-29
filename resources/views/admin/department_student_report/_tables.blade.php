<h2>Department Wise Registered / Unregistered Students</h2>
@if (!empty($eventTitle))
    <p class="subtitle">Event: {{ $eventTitle }}</p>
@endif

<h3>Department Summary</h3>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Department</th>
            <th>Total Students</th>
            <th>Registered</th>
            <th>Unregistered</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($summary as $index => $row)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $row->name }}</td>
                <td class="text-center">{{ $row->total }}</td>
                <td class="text-center">{{ $row->registered }}</td>
                <td class="text-center">{{ $row->unregistered }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center">No records found</td>
            </tr>
        @endforelse
        @if ($summary->isNotEmpty())
            <tr>
                <th colspan="2">Total</th>
                <th>{{ $summary->sum('total') }}</th>
                <th>{{ $summary->sum('registered') }}</th>
                <th>{{ $summary->sum('unregistered') }}</th>
            </tr>
        @endif
    </tbody>
</table>

<h3>Student List</h3>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Register Number</th>
            <th>Student Name</th>
            <th>Department</th>
            <th>Programme</th>
            <th>Batch</th>
            <th>Semester</th>
            <th>Section</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($students as $index => $student)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $student->register_number ?? '-' }}</td>
                <td>{{ $student->name }}</td>
                <td>{{ $student->get_department->name ?? '-' }}</td>
                <td>{{ $student->get_programme->name ?? '-' }}</td>
                <td class="text-center">{{ $student->batch ?? '-' }}</td>
                <td class="text-center">{{ $student->semester ?? '-' }}</td>
                <td class="text-center">{{ strtoupper($student->section ?? '-') }}</td>
                <td class="text-center">{{ $student->is_registered ? 'Registered' : 'Unregistered' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center">No records found</td>
            </tr>
        @endforelse
    </tbody>
</table>
