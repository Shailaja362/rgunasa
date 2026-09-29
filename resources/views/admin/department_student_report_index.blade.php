<x-layouts.app>
    <div class="bg-[#F5E8F5] w-full h-[50px] rounded-full shadow-sm px-8 py-3 flex flex-col justify-center">
        <h3 class="font-semibold text-primary">Department Wise Students Report</h3>
    </div>
    <div class="max-w-8xl mx-auto px-4 py-8">
        {{-- Page Header --}}
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">
                Registered / Unregistered Students
            </h1>
            {{-- Export Buttons --}}
            <div class="flex gap-2">
                <a href="{{ route('department_student_report.export', ['type' => 'excel'] + request()->query()) }}"
                    class="px-4 py-2 text-sm bg-[#ff7f50] text-white rounded">
                    Export Excel
                </a>
                <a href="{{ route('department_student_report.export', ['type' => 'pdf'] + request()->query()) }}"
                    class="px-4 py-2 text-sm bg-[#C04000] text-white rounded">
                    Export PDF
                </a>
                <a href="{{ route('department_student_report.export', ['type' => 'word'] + request()->query()) }}"
                    class="px-4 py-2 text-sm bg-[#E34234] text-white rounded">
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
                    <label class="block text-sm font-medium mb-1">Programme</label>
                    <select name="programme_id" class="w-full border rounded px-3 py-2 choice-select">
                        <option value="">All Programmes</option>
                        @foreach ($programmes as $programme)
                            <option value="{{ $programme->id }}"
                                {{ request('programme_id') == $programme->id ? 'selected' : '' }}>
                                {{ $programme->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Event Date</label>
                    <input type="date" name="event_date" value="{{ request('event_date') }}"
                        class="w-full border rounded px-3 py-2">
                </div>
            </div>
            <div class="mt-6 flex justify-center gap-4">
                <button type="submit"
                    class="px-6 py-2 text-sm bg-gradient-to-r from-primary to-pink-600 text-white rounded-md hover:bg-indigo-700 transition">
                    Apply Filters
                </button>
                <a href="{{ route('department_student_report') }}"
                    class="px-6 py-2 text-sm border rounded-md bg-gray-600 text-white hover:bg-gray-600 transition">
                    Reset
                </a>
            </div>
        </form>

        {{-- Department Summary --}}
        <h2 class="text-lg font-semibold mb-3">Department Summary</h2>
        <div class="bg-white rounded-lg shadow overflow-x-auto mb-8">
            <table class="min-w-full border-collapse">
                <thead class="bg-primary text-white uppercase text-sm">
                    <tr>
                        <th class="px-2 py-3 text-left text-sm font-semibold">S.No</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Department</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Total Students</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Registered</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Unregistered</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($summary as $index => $row)
                        <tr class="border-t">
                            <td class="px-2 py-3">{{ $index + 1 }}</td>
                            <td class="px-2 py-3">{{ $row->name }}</td>
                            <td class="px-2 py-3">{{ $row->total }}</td>
                            <td class="px-2 py-3 text-green-700 font-medium">{{ $row->registered }}</td>
                            <td class="px-2 py-3 text-red-700 font-medium">{{ $row->unregistered }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-6 text-gray-500">No departments found</td>
                        </tr>
                    @endforelse
                    @if ($summary->isNotEmpty())
                        <tr class="border-t bg-gray-50 font-semibold">
                            <td class="px-2 py-3" colspan="2">Total</td>
                            <td class="px-2 py-3">{{ $summary->sum('total') }}</td>
                            <td class="px-2 py-3">{{ $summary->sum('registered') }}</td>
                            <td class="px-2 py-3">{{ $summary->sum('unregistered') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        {{-- Student List --}}
        <h2 class="text-lg font-semibold mb-3 mt-4">Student List</h2>
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full border-collapse">
                <thead class="bg-primary text-white uppercase text-sm">
                    <tr>
                        <th class="px-2 py-3 text-left text-sm font-semibold">S.No</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Register Number</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Student</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Department</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Programme</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Batch</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Semester</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Section</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $index => $student)
                        <tr class="border-t">
                            <td class="px-2 py-3">{{ $students->firstItem() + $index }}</td>
                            <td class="px-2 py-3">{{ $student->register_number ?? '-' }}</td>
                            <td class="px-2 py-3">{{ $student->name }}</td>
                            <td class="px-2 py-3">{{ $student->get_department?->name ?? '-' }}</td>
                            <td class="px-2 py-3">{{ $student->get_programme?->name ?? '-' }}</td>
                            <td class="px-2 py-3">{{ $student->batch ?? '-' }}</td>
                            <td class="px-2 py-3">{{ $student->semester ?? '-' }}</td>
                            <td class="px-2 py-3">{{ strtoupper($student->section ?? '-') }}</td>
                            <td class="px-2 py-3">
                                @if ($student->is_registered)
                                    <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">Registered</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-700">Unregistered</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-6 text-gray-500">No students found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- Pagination --}}
        <div class="mt-4">
            {{ $students->withQueryString()->links() }}
        </div>
    </div>
</x-layouts.app>
