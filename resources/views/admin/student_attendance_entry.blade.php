<x-layouts.app>
    <!-- HEADER -->
    <div class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-6 py-4 flex justify-between items-center">
        <div>
            <h3 class="font-semibold text-primary text-lg">Student Attendance Management</h3>
            <p class="text-sm text-gray-700">Mark entry & exit</p>
        </div>
        <a href="{{ route('student_attendance') }}" class="flex items-center text-gray-700 hover:text-primary">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back
        </a>
    </div>
    <!-- TOAST -->
    @if (session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                showToast("{{ session('success') }}", "success");
            });
        </script>
    @endif
    <!-- FILTERS -->
    <form method="GET" action="{{ route('student_attendance_entry') }}" class="mt-4" id="searchAttendanceForm">
        <input type="hidden" name="event_id" value="{{ $event->id }}">
        <div class="bg-white p-4 rounded-2xl shadow">
            <div class="grid grid-cols-2 md:grid-cols-2 gap-2">
                <div>
                    <label class="block text-sm font-medium">Programme</label>
                    <select name="programme_id" class="border rounded-lg px-3 py-3 w-full mt-2 choice-select" id="programme_id">
                        <option value="">-- Select Programme --</option>
                        @foreach ($get_schedule_event as $id => $value)
                           @if (!empty($value->programme))
                           <option value="{{ $value->programme->id }}"
                               {{ request('programme_id') == $value->programme->id ? 'selected' : '' }}>
                               {{ $value->programme->name ?? '' }}
                           </option>
                           @endif
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Event Date</label>
                    <input type="date" name="event_date" id="event_date" value="{{ request('event_date') }}"
                        class="border rounded-lg px-3 py-2 w-full mt-2">
                </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                <div>
                    <label class="block text-sm font-medium"> Section <span class="text-red-500">*</span></label>
                    <select name="section" id="section" class="border rounded-lg px-3 py-2 w-full mt-2 choice-select">
                        <option value="" selected disabled>Select Section</option>
                        <option value="a" {{ request('section') == 'a' ? 'selected' : '' }}>A</option>
                        <option value="b" {{ request('section') == 'b' ? 'selected' : '' }}>B</option>
                        <option value="c" {{ request('section') == 'c' ? 'selected' : '' }}>C</option>
                        <option value="d" {{ request('section') == 'd' ? 'selected' : '' }}>D</option>
                        <option value="r" {{ request('section') == 'r' ? 'selected' : '' }}>R</option>
                    </select>
                </div>
                @php
                    $currentProgrammeOptions = $programmeScheduleOptions[request('programme_id')] ?? [
                        'batches' => collect(),
                        'semesters' => collect(),
                    ];
                    $requestedBatches = (array) request('batch', []);
                    $requestedSemesters = (array) request('semester', []);
                @endphp
                <div>
                    <label class="block text-sm font-medium">Batch<span class="text-red-500">*</span></label>
                    <select name="batch[]" id="batch" multiple
                        class="batch w-full p-2 border border-gray-300 rounded-full focus:outline-none focus:ring focus:ring-primary/40">
                        @foreach ($currentProgrammeOptions['batches'] as $batchOption)
                            <option value="{{ $batchOption }}" {{ in_array($batchOption, $requestedBatches) ? 'selected' : '' }}>
                                {{ $batchOption }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Select a Programme to load available batches.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium"> Semester <span class="text-red-500">*</span></label>
                    <select name="semester[]" id="semester" multiple
                        class="semester w-full p-2 border border-gray-300 rounded-full focus:outline-none focus:ring focus:ring-primary/40">
                        @foreach ($currentProgrammeOptions['semesters'] as $semesterOption)
                            <option value="{{ $semesterOption }}" {{ in_array($semesterOption, $requestedSemesters) ? 'selected' : '' }}>
                                {{ $semesterOption }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Select a Programme to load available semesters.</p>
                </div>
            </div>
            <div class="text-center mt-4">
                <button class="bg-primary text-white px-6 py-2 rounded-full shadow">
                    <i class="fa fa-search mr-1"></i> Search
                </button>
                <a href="{{ route('student_attendance_entry', ['event_id' => $event->id]) }}"
                    class="px-6 py-2 text-sm border rounded-md bg-gray-600 text-white hover:bg-gray-600 transition">
                    Reset
                </a>
            </div>
        </div>
    </form>
    @if (request()->filled('programme_id') && request()->filled('event_date'))
        <form method="POST" action="{{ route('attendance.mark') }}">
            @csrf
            <input type="hidden" name="event_id" value="{{ $event->id }}">
            <input type="hidden" name="schedule_id" value="{{ $schedule->id ?? '' }}">
            @php
                $anyEntryExists = $attendance_entry->whereNotNull('entry_time')->count() > 0;
                $anyExitExists = $attendance_entry->whereNotNull('exit_time')->count() > 0;
            @endphp
            <div class="mt-4 bg-white rounded-2xl shadow overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th class="px-4 py-3">S.No</th>
                            <th class="px-4 py-3">Register No</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Programme</th>
                            <th class="px-4 py-3">Section</th>
                            <th class="px-4 py-3 text-center">
                                Entry <br>
                                <input type="checkbox" id="selectAllEntry">
                            </th>
                            <th class="px-4 py-3 text-center">
                                Exit <br>
                                <input type="checkbox" id="selectAllExit">
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($registeredStudents as $attendance)
                            @php
                                $studentEntryExists =
                                    $attendance_entry
                                        ->where('student_id', $attendance->student_id)
                                        ->where('event_schedule_id', $attendance->event_schedule_id)
                                        ->whereNotNull('entry_time')
                                        ->count() > 0;
                                $studentExitExists =
                                    $attendance_entry
                                        ->where('student_id', $attendance->student_id)
                                        ->where('event_schedule_id', $attendance->event_schedule_id)
                                        ->whereNotNull('exit_time')
                                        ->count() > 0;
                            @endphp
                            <tr class="border-t">
                                <td class="px-4 py-3">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3">{{ $attendance->student?->register_number }}</td>
                                <td class="px-4 py-3">{{ $attendance->student?->name }}</td>
                                <td class="px-4 py-3">{{ $attendance->student?->get_programme?->name }}</td>
                                <td class="px-4 py-3">{{ $attendance->student?->section }}</td>
                                <td class="px-4 py-3 text-center">
                                    {{-- <input type="checkbox" name="attendance[{{ $attendance->student_id }}][entry]"
                                        class="entry-checkbox" {{ $studentEntryExists ? 'checked disabled' : '' }}
                                        {{ $anyEntryExists ? 'disabled' : '' }}> --}}
                                    <input type="hidden" name="attendance[{{ $attendance->student_id }}][entry]"
                                        value="0">
                                    <input type="checkbox" name="attendance[{{ $attendance->student_id }}][entry]"
                                        class="entry-checkbox" {{ $studentEntryExists ? 'checked' : '' }}
                                        value="1" {{ $anyEntryExists }}>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    {{-- <input type="checkbox" name="attendance[{{ $attendance->student_id }}][exit]"
                                        class="exit-checkbox" {{ $studentExitExists ? 'checked disabled' : '' }}
                                        {{ $anyExitExists ? 'disabled' : '' }}> --}}
                                    <input type="hidden" name="attendance[{{ $attendance->student_id }}][exit]"
                                        value="0">
                                    <input type="checkbox" name="attendance[{{ $attendance->student_id }}][exit]"
                                        class="exit-checkbox" {{ $studentExitExists ? 'checked' : '' }} value="1"
                                        {{ $anyExitExists }}>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-6 text-gray-500">
                                    No students found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5 text-center">
                <button class="bg-primary text-white px-8 py-2 rounded-full">
                    Submit Attendance
                </button>
            </div>
        </form>
    @else
        <div class="mt-6 text-center text-gray-500">
            Please select <b>Department</b> and <b>Event Date</b> to view students
        </div>
    @endif

</x-layouts.app>

<script>
    const programmeScheduleOptions = @json($programmeScheduleOptions);

    const batchChoice = new Choices("#batch", {
        removeItemButton: true,
        searchEnabled: true,
        shouldSort: false,
        placeholderValue: "Select Batch",
    });
    const semesterChoice = new Choices("#semester", {
        removeItemButton: true,
        searchEnabled: true,
        shouldSort: false,
        placeholderValue: "Select Semester",
    });

    function populateProgrammeScheduleOptions(programmeId) {
        const options = programmeScheduleOptions[programmeId] || { batches: [], semesters: [] };
        batchChoice.clearStore();
        batchChoice.setChoices(
            options.batches.map((b) => ({ value: b, label: b })),
            "value",
            "label",
            true,
        );
        semesterChoice.clearStore();
        semesterChoice.setChoices(
            options.semesters.map((s) => ({ value: s, label: s })),
            "value",
            "label",
            true,
        );
    }

    $(document).on('change', '#programme_id', function() {
        populateProgrammeScheduleOptions(this.value);
    });

    document.getElementById('selectAllEntry')?.addEventListener('change', function() {
        document.querySelectorAll('.entry-checkbox:not(:disabled)').forEach(cb => cb.checked = this.checked);
    });
    document.getElementById('selectAllExit')?.addEventListener('change', function() {
        document.querySelectorAll('.exit-checkbox:not(:disabled)').forEach(cb => cb.checked = this.checked);
    });
$(document).ready(function () {
    $("#searchAttendanceForm").on("submit", function (e) {
        let programme = $("#programme_id").val();
        let event_date = $("#event_date").val();
        let section = $("#section").val();
        let batch = $("#batch").val() || [];
        let semester = $("#semester").val() || [];
        let error = false;
        $(".error-text").remove();
        $(".border-red-500").removeClass("border-red-500");
        if (!programme) {
            $("#programme_id")
                .addClass("border-red-500")
                .after("<span class='error-text text-red-500 text-sm'>Programme is required</span>");
            error = true;
        }
        if (!event_date) {
            $("#event_date")
                .addClass("border-red-500")
                .after("<span class='error-text text-red-500 text-sm'>Event Date is required</span>");
            error = true;
        }
        if (!section) {
            $("#section")
                .addClass("border-red-500")
                .after("<span class='error-text text-red-500 text-sm'>Section is required</span>");
            error = true;
        }
        if (batch.length === 0) {
            $("#batch")
                .addClass("border-red-500")
                .after("<span class='error-text text-red-500 text-sm'>Batch is required</span>");
            error = true;
        }
        if (semester.length === 0) {
            $("#semester")
                .addClass("border-red-500")
                .after("<span class='error-text text-red-500 text-sm'>Semester is required</span>");
            error = true;
        }
        if (error) {
            e.preventDefault();
        }
    });
});
</script>
