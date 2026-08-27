<x-layouts.app>
    <div class="bg-[#F5E8F5] w-full h-[50px] rounded-full shadow-sm px-8 py-3 flex flex-col justify-center">
        <h3 class="font-semibold text-primary">Event Registered Students Report</h3>
    </div>
    <div class="max-w-8xl mx-auto px-4 py-8">
        {{-- Page Header --}}
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">
                Event Registration List
            </h1>
            {{-- Export Buttons --}}
            <div class="flex gap-2">
                <a href="{{ route('admin.event-registrations.export', ['type' => 'excel'] + request()->query()) }}"
                    class="export-btn px-4 py-2 text-sm bg-[#ff7f50] text-white rounded">
                    Export Excel
                </a>
                <a href="{{ route('admin.event-registrations.export', ['type' => 'csv'] + request()->query()) }}"
                    class="export-btn px-4 py-2 text-sm bg-[#E27258] text-white rounded">
                    Export CSV
                </a>
                <a href="{{ route('admin.event-registrations.export', ['type' => 'pdf'] + request()->query()) }}"
                    class="export-btn px-4 py-2 text-sm bg-[#C04000] text-white rounded">
                    Export PDF
                </a>
                <a href="{{ route('admin.event-registrations.export', ['type' => 'word'] + request()->query()) }}"
                    class="export-btn px-4 py-2 text-sm bg-[#E34234] text-white rounded">
                    Export Word
                </a>
            </div>
        </div>
        <form method="GET" action="" class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Event</label>
                    <select name="event_id" class="w-full border rounded px-3 py-2 choice-select">
                        <option value="">All Events</option>
                        @foreach ($events as $event)
                            <option value="{{ $event->id }}"
                                {{ request('event_id') == $event->id ? 'selected' : '' }}>
                                {{ $event->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Status</label>
                    <select name="status" class="w-full border rounded px-3 py-2 choice-select">
                        <option value="">All</option>
                        @foreach ($statusLabels as $key => $label)
                            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}"
                        class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}"
                        class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Student name / Email" class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium">Batch</label>
                    <select name="batch" id="batch"
                        class="batch w-full p-2 border border-gray-300 rounded-full focus:outline-none focus:ring focus:ring-primary/40 choice-select">
                        <option value="">Select Batch</option>
                        @foreach ($batches as $batchOption)
                            <option value="{{ $batchOption }}" {{ request('batch') == $batchOption ? 'selected' : '' }}>
                                {{ $batchOption }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium"> Semester</label>
                    <select name="semester" id="semester"
                        class="semester w-full p-2 border border-gray-300 rounded-full focus:outline-none focus:ring focus:ring-primary/40 choice-select">
                        <option value="" selected disabled>Select Semester</option>
                        <option value="1" {{ request('semester') == '1' ? 'selected' : '' }}>1</option>
                        <option value="2" {{ request('semester') == '2' ? 'selected' : '' }}>2</option>
                        <option value="3" {{ request('semester') == '3' ? 'selected' : '' }}>3</option>
                        <option value="4" {{ request('semester') == '4' ? 'selected' : '' }}>4</option>
                        <option value="5" {{ request('semester') == '5' ? 'selected' : '' }}>5</option>
                        <option value="6" {{ request('semester') == '6' ? 'selected' : '' }}>6</option>
                        <option value="7" {{ request('semester') == '7' ? 'selected' : '' }}>7</option>
                        <option value="8" {{ request('semester') == '8' ? 'selected' : '' }}>8</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex justify-center gap-4">
                <button type="submit"
                    class="px-6 py-2 text-sm bg-gradient-to-r from-primary to-pink-600 text-white rounded-md hover:bg-indigo-700 transition">
                    Apply Filters
                </button>
                <a href="{{ route('registered_report_index') }}"
                    class="px-6 py-2 text-sm border rounded-md bg-gray-600 text-white hover:bg-gray-600 transition">
                    Reset
                </a>
            </div>
        </form>
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full border-collapse">
                <thead class="bg-primary text-white uppercase text-sm">
                    <tr>
                        <th class="px-2 py-3 text-left text-sm font-semibold">S.No</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Register Number</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Student</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Department</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Section</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Event</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Event Date</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Registered At</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($registrations as $index => $row)
                        <tr class="border-t">
                            <td class="px-2 py-3">{{ $registrations->firstItem() + $index }}</td>
                            <td class="px-2 py-3">{{ $row->student?->register_number }}</td>
                            <td class="px-2 py-3">{{ $row->student?->name }}</td>
                            <td class="px-2 py-3">{{ $row->student?->get_department?->name }}</td>
                            <td class="px-2 py-3">{{ $row->student?->section }}</td>
                            <td class="px-2 py-3">{{ $row->event->title }}</td>
                            <td class="px-2 py-3">
                                @if ($row->get_event_schedule)
                                    {{ \Carbon\Carbon::parse($row->get_event_schedule->event_date)->format('d M Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-2 py-3">{{ $row->created_at->format('d M Y') }}</td>
                            <td class="px-2 py-3">
                                <span
                                    class="px-2 py-1 text-xs rounded {{ $statusClasses[$row->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $statusLabels[$row->status] ?? 'Unknown' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-6 text-gray-500">
                                No registrations found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- Pagination --}}
        <div class="mt-4">
            {{ $registrations->withQueryString()->links() }}
        </div>
    </div>
</x-layouts.app>
<script>
    $(document).on('click', '.export-btn', function(e) {

        let eventId = $('select[name="event_id"]').val();
        let status = $('select[name="status"]').val();
        let fromDate = $('input[name="from_date"]').val();
        let toDate = $('input[name="to_date"]').val();
        let search = $('input[name="search"]').val();
        let semester = $('select[name="semester"]').val();
        let batch = $('select[name="batch"]').val();

        // Check if all fields are empty
        if (
            eventId === '' &&
            status === '' &&
            fromDate === '' &&
            toDate === '' &&
            search.trim() === '' && semester === '' && batch === ''
        ) {
            e.preventDefault();
            showToast('Please select at least one filter before downloading the report.', "error", 2000);
            setTimeout(() => 800);
            return false;
        }
    });
</script>
