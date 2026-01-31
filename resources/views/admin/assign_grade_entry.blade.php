<x-layouts.app>
    {{-- Header --}}
    <div
        class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-6 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <h3 class="font-semibold text-primary text-lg sm:text-xl">Student Assign Grades</h3>
        <a href="{{ route('assign_grades') }}"
            class="flex items-center text-gray-700 hover:text-primary transition-colors">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back
        </a>
    </div>
    {{-- Success Message --}}
    @if (session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                showToast("{{ session('success') }}", "success");
            });
        </script>
    @endif
    @if (session('error'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                showToast("{{ session('error') }}", "error");
            });
        </script>
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="mt-6 rounded-lg bg-red-100 border border-red-200 p-4 text-red-800">
            <strong class="block mb-1">Please fix the following errors:</strong>
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <h1 class="text-lg font-semibold text-gray-800 mt-4">
        Event : {{ $event->title ?? '' }}
    </h1>
    <form method="GET" action="{{ route('assign_grade_entry') }}" class="mt-4">
        <input type="hidden" name="event_id" value="{{ $event->id }}">
        <div class="bg-white p-4 rounded-2xl shadow">
            <div class="grid grid-cols-2 md:grid-cols-2 gap-2">
                <div>
                    <label class="block text-sm font-medium mb-1">Department</label>
                    <select name="department_id" class="border rounded-lg px-3 py-2 w-full">
                        <option value="">-- Select Department --</option>
                        @foreach ($schedule_department as $dept)
                            <option value="{{ $dept->department->id }}"
                                {{ request('department_id') == $dept->department->id ? 'selected' : '' }}>
                                {{ $dept->department->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Event Date</label>
                    <input type="date" name="event_date" value="{{ request('event_date') }}"
                        class="border rounded-lg px-3 py-2 w-full">
                </div>
            </div>
            <div class="text-center mt-4">
                <button class="bg-primary text-white px-6 py-2 rounded-full shadow">
                    <i class="fa fa-search mr-1"></i> Search
                </button>
                <a href="{{ route('assign_grade_entry', ['event_id' => $event->id]) }}"
                    class="px-6 py-2 text-sm border rounded-md bg-gray-600 text-white hover:bg-gray-600 transition">
                    Reset
                </a>
            </div>
        </div>
    </form>

    @if (request()->filled('department_id') && request()->filled('event_date'))
        <form method="POST" action="{{ route('grade_save') }}" class="mt-8">
            @csrf
            <input type="hidden" name="event_id" value="{{ $event->id }}">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-5">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y text-sm mt-5">
                        <thead>
                            <tr class="bg-primary text-white uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">S.No</th>
                                <th class="px-4 py-3 text-left font-semibold">Register Number</th>
                                <th class="px-4 py-3 text-left font-semibold">Student Name</th>
                                <th class="px-4 py-3 text-left font-semibold">Department Name</th>
                                <th class="px-4 py-3 text-left font-semibold">Section</th>
                                <th class="px-4 py-3 text-left font-semibold">Student Report</th>
                                <th class="px-4 py-3 text-left font-semibold">Download Files</th>
                                <th class="px-4 py-3 text-left font-semibold">Grade</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($registrations as $index => $registration)
                                @php
                                    $feedback = $registration->get_feedback?->first();
                                    $proofs = $registration->get_student_upload_proof ?? collect();
                                    $grade = $registration->grades->first();
                                @endphp
                                <tr class="hover:bg-gray-50 transition">
                                    <input type="hidden" name="schedule_id"
                                        value="{{ $registration->event_schedule_id }}">
                                    <td class="px-4 py-3">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3">{{ $registration->student->register_number ?? '' }}</td>
                                    <td class="px-4 py-3">{{ $registration->student->name ?? '' }}</td>
                                    <td class="px-4 py-3">{{ $registration->student->get_department->name ?? '' }}</td>
                                    <td class="px-4 py-3">{{ $registration->student->section ?? '' }}</td>
                                    <td class="px-4 py-3">
                                        @if ($feedback && $proofs->count() > 0)
                                            <a href="{{ route('student_event_report', ['student' => $registration->student_id, 'event' => $event->id, 'schedule_id' => $registration->event_schedule_id]) }}"
                                                target="_blank"
                                                class="bg-green-600 text-white px-4 py-2 rounded-full text-sm">
                                                View Report
                                            </a>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @php
                                            $docFiles = $proofs->filter(function ($file) {
                                                $ext = strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION));
                                                return in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx']);
                                            });
                                        @endphp

                                        @if ($docFiles->count() > 0)
                                            <a href="javascript:void(0)"
                                                class="bg-blue-600 text-white px-4 py-1 rounded-full text-sm downloadAllBtn"
                                                data-event_id="{{ $event->id }}"
                                                data-student_id="{{ $registration->student_id }}"
                                                data-schedule_id="{{ $registration->event_schedule_id }}">
                                                Download All Files
                                            </a>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <select name="grades[{{ $registration->student_id }}]">
                                            <option value="">Select Grade</option>
                                            <option value="a" {{ $grade?->grade === 'a' ? 'selected' : '' }}>A
                                            </option>
                                            <option value="b" {{ $grade?->grade === 'b' ? 'selected' : '' }}>B
                                            </option>
                                            <option value="c" {{ $grade?->grade === 'c' ? 'selected' : '' }}>C
                                            </option>
                                            <option value="d" {{ $grade?->grade === 'd' ? 'selected' : '' }}>D
                                            </option>
                                        </select>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-gray-500 py-4">
                                        No attendance records found for the selected department and date. Please enter
                                        the attendance.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($registrations->count() > 0)
                    <div class="mt-5 text-center py-5">
                        <button type="submit" class="bg-primary text-white px-8 py-2 rounded-full">
                            Save Grades
                        </button>
                    </div>
                @endif
        </form>
    @else
        <div class="mt-6 text-center text-gray-500">
            Please select <b>Department</b> and <b>Event Date</b> to view students.
        </div>
    @endif
    </div>
</x-layouts.app>
<script>
    $(document).on('click', '.downloadAllBtn', function(e) {
        e.preventDefault();
        const eventId = $(this).data('event_id');
        const studentId = $(this).data('student_id');
        fetch(`/admin/download-otherfiles/${eventId}/${studentId}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success || !Array.isArray(data.files)) {
                    alert(data.message || 'No files to download.');
                    return;
                }
                data.files.forEach(file => {
                    const a = document.createElement('a');
                    a.href = file.url;
                    a.download = file.name; // triggers browser download
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                });
            })
            .catch(err => {
                console.error(err);
                alert('Something went wrong while downloading files.');
            });

    });
</script>
