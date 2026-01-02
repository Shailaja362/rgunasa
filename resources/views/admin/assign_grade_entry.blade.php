<x-layouts.app>
    {{-- Header --}}
    <div class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-6 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
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

    {{-- Assign Grades Form --}}
    <form method="POST" action="{{ route('grade_save') }}" class="mt-8">
        @csrf
        <input type="hidden" name="event_id" value="{{ $event->id ?? '' }}">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-5">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y text-sm">
                    <thead>
                        <tr class="bg-primary text-white uppercase tracking-wider">
                            <th class="px-4 py-3 text-left font-semibold">S.No</th>
                            <th class="px-4 py-3 text-left font-semibold">Register Number</th>
                            <th class="px-4 py-3 text-left font-semibold">Student Name</th>
                            <th class="px-4 py-3 text-left font-semibold">Department Name</th>
                            <th class="px-4 py-3 text-left font-semibold">Section</th>
                            <th class="px-4 py-3 text-left font-semibold">Grade</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($registrations as $index => $registration)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3">
                                    {{ $index + 1 }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ $registration->student->register_number ?? '' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-800">
                                        {{ $registration->student->name }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    {{ $registration->student->get_department->name ?? '' }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ $registration->student->section ?? '' }}
                                </td>
                                <td class="px-4 py-3">
                                    <select name="grades[{{ $registration->student_id }}]"
                                        class="py-2 w-32 rounded-lg border-gray-300 text-sm focus:border-primary focus:ring-primary"
                                        required>
                                        <option value="">Select Grade</option>
                                        <option value="a"
                                            {{ $registration->get_grade->grade == 'a' ? 'selected' : '' }}>A - Winner
                                        </option>
                                        <option value="b"
                                            {{ $registration->get_grade->grade == 'b' ? 'selected' : '' }}>B - Runner
                                            Up
                                        </option>
                                        <option value="c"
                                            {{ $registration->get_grade->grade == 'c' ? 'selected' : '' }}>C -
                                            Completed
                                        </option>
                                        <option value="d"
                                            {{ $registration->get_grade->grade == 'd' ? 'selected' : '' }}>D -
                                            Disqualified
                                        </option>
                                    </select>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-gray-500">
                                   No attendance records found for this event.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer --}}
            @if ($registrations->count() > 0)
                <div class="px-5 py-4 border-t border-gray-200  mt-5 text-center">
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-6 py-2
                               bg-gradient-to-r from-primary to-pink-600
                               text-white text-sm font-medium rounded-lg transition">
                        <i class="fa fa-save"></i>
                        Save Grades
                    </button>
                </div>
            @endif
        </div>
    </form>

</x-layouts.app>
