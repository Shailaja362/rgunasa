<x-layouts.app>
    {{-- Header --}}
    <div
        class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-6 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <h3 class="font-semibold text-primary text-lg sm:text-xl">Student Credit Points</h3>
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

    <form method="GET" action="{{ route('student_credits') }}" class="mt-4">
        <div class="bg-white p-4 rounded-2xl shadow">
            <div class="grid grid-cols-2 md:grid-cols-2 gap-2">
                <div>
                    <label class="block text-sm font-medium mb-1">Programme</label>
                    <select name="programme_id" id="programme_id"
                        class="border rounded-lg px-3 py-2 w-full choice-select">
                        <option value="">-- Select Programme --</option>
                        @foreach ($programmes as $prog)
                            <option value="{{ $prog->id }}"
                                {{ request('programme_id') == $prog->id ? 'selected' : '' }}>
                                {{ $prog->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium"> Semester <span class="text-red-500">*</span></label>
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
            </div>
        </div>
        <div class="text-center mt-4">
            <button class="bg-primary text-white px-6 py-2 rounded-full shadow">
                <i class="fa fa-search mr-1"></i> Search
            </button>
            <a href="{{ route('student_credits') }}"
                class="px-6 py-2 text-sm border rounded-md bg-gray-600 text-white hover:bg-gray-600 transition">
                Reset
            </a>
        </div>
        </div>
    </form>

    @if (request()->filled('programme_id') && request()->filled('semester'))
    <form method="POST" action="{{ route('grade_save') }}" class="mt-8 p-5">
        @csrf
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-5 p-5">
                <div class="flex justify-end mb-4">
                <a href="{{ route('export_student_credits', [
                    'programme_id' => request()->programme_id,
                    'semester' => request()->semester,
                    'section' => request()->section
                ]) }}"
                   class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
            </div>
                <div class="overflow-x-auto">
                    <div>
                        <h1><b>Programme Name : </b>{{ $progm->name ?? '' }} <br>
                            <b>Semester : </b> {{ request()->semester ?? '' }} <br>
                            <b>Credit Point :</b>
                            {{ isset($credit_points->credit_points) ? round($credit_points->credit_points) : '' }}
                            <b>Section : {{ request()->section ?? '' }}</b>
                        </h1>
                    </div>
                    <table class="min-w-full divide-y text-sm mt-5">
                        <thead>
                            <tr class="bg-primary text-white uppercase tracking-wider">
                                <th class="px-2 py-3 text-left font-semibold">S.No</th>
                                <th class="px-2 py-3 text-left font-semibold">Register Number</th>
                                <th class="px-2 py-3 text-left font-semibold">Student Name</th>
                                <th class="px-2 py-3 text-left font-semibold">Attended Events</th>
                                <th class="px-2 py-3 text-left font-semibold">Earned Credit Points</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($student_credits as $index => $student)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-2 py-3">{{ $index + 1 }}</td>
                                    <td class="px-2 py-3">{{ $student->register_number ?? '' }}</td>
                                    <td class="px-2 py-3">{{ $student->name ?? '' }}</td>
                                    <td class="px-2 py-3">
                                        @if ($student->registrations->count())
                                            <div class="flex flex-col gap-2">
                                                @foreach ($student->registrations as $registration)
                                                    <div class="px-3 py-2 rounded-lg bg-blue-50 border border-blue-200">
                                                        <div class="font-medium text-blue-800">
                                                            {{ $registration->get_event_schedule->event->title ?? '' }}
                                                        </div>
                                                        <div class="text-sm text-blue-600">
                                                            {{ isset($registration->get_event_schedule->credit_points)
                                                                ? round($registration->get_event_schedule->credit_points)
                                                                : '' }}
                                                            Credits
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-400">No Events</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-3 font-semibold text-green-600 text-center">
                                        {{ $student->earned_credits ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-gray-500 py-4">
                                        No students found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
        </form>
    @else
        <div class="mt-6 text-center text-gray-500">
            Please select <b>Department</b> and <b>Event Date</b> to view students.
        </div>
    @endif
    </div>
</x-layouts.app>
<script>
    $(document).ready(function() {
        $("form").on("submit", function(e) {
            let programme = $("#programme_id").val();
            let semester = $("#semester").val();
            let section = $("#section").val();
            let error = false;
            $(".error-text").remove();
            $(".border-red-500").removeClass("border-red-500");
            if (!programme) {
                $("#programme_id")
                    .addClass("border-red-500")
                    .after(
                        "<span class='error-text text-red-500 text-sm'>Programme is required</span>");
                error = true;
            }

            if (!section) {
                $("#section")
                    .addClass("border-red-500")
                    .after("<span class='error-text text-red-500 text-sm'>Section is required</span>");
                error = true;
            }

            if (!semester) {
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
