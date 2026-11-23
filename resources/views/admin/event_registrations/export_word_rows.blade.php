@foreach ($registrations as $index => $row)
    <tr>
        <td>{{ $offset + $index + 1 }}</td>
        <td>{{ $row->student->name ?? '-' }}</td>
        <td>{{ $row->student->batch ?? '-' }}</td>
        <td>{{ $row->student->semester ?? '-' }}</td>
        <td>{{ $row->student->email ?? '-' }}</td>
        <td>{{ $row->event->title ?? '-' }}</td>
        <td>{{ $statusLabels[$row->status] ?? 'Unknown' }}</td>
        <td>{{ optional($row->created_at)->format('d-m-Y') }}</td>
    </tr>
@endforeach
