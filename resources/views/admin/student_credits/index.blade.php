<x-layouts.app>
    {{-- Header --}}
    <div
        class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-6 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <h3 class="font-semibold text-primary text-lg sm:text-xl">Student Credit Report</h3>
    </div>

    @if (session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                showToast(@json(session('success')), "success");
            });
        </script>
    @endif
    @if (session('error'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                showToast(@json(session('error')), "error");
            });
        </script>
    @endif

    {{-- Filters: semester is required (required credits are set per semester) --}}
    <form method="GET" action="{{ route('credit_report') }}" id="credit-filter-form" class="mt-4">
        <div class="bg-white p-4 rounded-2xl shadow">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Semester <span class="text-red-500">*</span></label>
                    <select name="semester" id="semester" class="w-full border rounded-lg px-3 py-2 choice-select">
                        <option value="">Select Semester</option>
                        @for ($sem = 1; $sem <= 8; $sem++)
                            <option value="{{ $sem }}" {{ ($filters['semester'] ?? '') == $sem ? 'selected' : '' }}>{{ $sem }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Programme</label>
                    <select name="programme_id" id="programme_id" class="w-full border rounded-lg px-3 py-2 choice-select">
                        <option value="">All Programmes</option>
                        @foreach ($programmes as $prog)
                            <option value="{{ $prog->id }}" {{ ($filters['programme_id'] ?? '') == $prog->id ? 'selected' : '' }}>
                                {{ $prog->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Section</label>
                    <select name="section" id="section" class="w-full border rounded-lg px-3 py-2 choice-select">
                        <option value="">All Sections</option>
                        @foreach (['a', 'b', 'c', 'd', 'e', 'f', 'r'] as $sec)
                            <option value="{{ $sec }}" {{ ($filters['section'] ?? '') == $sec ? 'selected' : '' }}>{{ strtoupper($sec) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Batch</label>
                    <select name="batch" id="batch" class="w-full border rounded-lg px-3 py-2 choice-select">
                        <option value="">All Batches</option>
                        @foreach ($batches as $batchOption)
                            <option value="{{ $batchOption }}" {{ ($filters['batch'] ?? '') == $batchOption ? 'selected' : '' }}>{{ $batchOption }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="text-center mt-4">
                <button type="submit" class="bg-primary text-white px-6 py-2 rounded-full shadow">
                    <i class="fa fa-search mr-1"></i> Search
                </button>
                <a href="{{ route('credit_report') }}"
                    class="px-6 py-2 text-sm border rounded-md bg-gray-600 text-white hover:bg-gray-600 transition">
                    Reset
                </a>
            </div>
        </div>
    </form>

    @if (!empty($filters['semester']))
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mt-6 p-5">
            {{-- Report heading + exports --}}
            <div class="flex flex-col md:flex-row items-start justify-between gap-4 mb-4">
                <div class="text-sm space-y-1">
                    <div><b>Semester:</b> {{ $filters['semester'] }}</div>
                    <div><b>Programme:</b> {{ $programmeName ?? 'All Programmes' }}</div>
                    @if (!empty($filters['section']))
                        <div><b>Section:</b> {{ strtoupper($filters['section']) }}</div>
                    @endif
                    @if (!empty($filters['batch']))
                        <div><b>Batch:</b> {{ $filters['batch'] }}</div>
                    @endif
                    <div>
                        <b>Required Credits:</b>
                        @if ($required === null)
                            <span class="text-red-600">Not set for semester {{ $filters['semester'] }}. Set it in
                                <a href="{{ route('credit_point_assign') }}" class="underline">Credit Point Assign</a>.</span>
                        @else
                            {{ \App\Services\Reports\StudentCreditReport::number($required) }}
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2" style="flex-shrink:0;">
                    <a href="{{ route('credit_report.export', ['type' => 'excel'] + $filters) }}"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm" style="white-space:nowrap;">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                    @if (!empty($filters['programme_id']))
                        <a href="{{ route('credit_report.export', ['type' => 'pdf'] + $filters) }}"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-[#C04000] text-white rounded-lg hover:opacity-90 transition text-sm" style="white-space:nowrap;">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                    @else
                        {{-- PDF of every programme is too large; Excel covers that case. --}}
                        <span title="Select a programme to download the PDF"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gray-300 text-gray-600 rounded-lg text-sm cursor-not-allowed" style="white-space:nowrap;">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </span>
                    @endif
                </div>
            </div>

            {{-- Summary across all matching students --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
                <div class="rounded-lg bg-blue-50 border border-blue-200 p-3">
                    <div class="text-xs text-blue-700">Students</div>
                    <div class="text-2xl font-bold text-blue-800">{{ $summary['students'] }}</div>
                </div>
                <div class="rounded-lg bg-green-50 border border-green-200 p-3">
                    <div class="text-xs text-green-700">Credits Completed</div>
                    <div class="text-2xl font-bold text-green-800">{{ $summary['completed'] ?? '-' }}</div>
                </div>
                <div class="rounded-lg bg-red-50 border border-red-200 p-3">
                    <div class="text-xs text-red-700">Credits Pending</div>
                    <div class="text-2xl font-bold text-red-800">{{ $summary['pending'] ?? '-' }}</div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y text-sm">
                    <thead>
                        <tr class="bg-primary text-white uppercase tracking-wider text-xs">
                            <th class="px-2 py-3 text-left font-semibold">S.No</th>
                            <th class="px-2 py-3 text-left font-semibold">Register Number</th>
                            <th class="px-2 py-3 text-left font-semibold">Student Name</th>
                            <th class="px-2 py-3 text-left font-semibold">Programme</th>
                            <th class="px-2 py-3 text-center font-semibold">Section</th>
                            <th class="px-2 py-3 text-center font-semibold">Batch</th>
                            <th class="px-2 py-3 text-left font-semibold">Credited Events</th>
                            <th class="px-2 py-3 text-center font-semibold">Required</th>
                            <th class="px-2 py-3 text-center font-semibold">Earned</th>
                            <th class="px-2 py-3 text-center font-semibold">Remaining</th>
                            <th class="px-2 py-3 text-center font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($rows as $row)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-2 py-3">{{ $row['serial'] }}</td>
                                <td class="px-2 py-3">{{ $row['register_number'] }}</td>
                                <td class="px-2 py-3">{{ $row['name'] }}</td>
                                <td class="px-2 py-3">{{ $row['programme'] }}</td>
                                <td class="px-2 py-3 text-center">{{ $row['section'] }}</td>
                                <td class="px-2 py-3 text-center">{{ $row['batch'] }}</td>
                                <td class="px-2 py-3 text-xs text-gray-600">{{ $row['events'] }}</td>
                                <td class="px-2 py-3 text-center font-semibold">{{ $row['required'] }}</td>
                                <td class="px-2 py-3 text-center font-semibold text-green-600">{{ $row['earned'] }}</td>
                                <td class="px-2 py-3 text-center font-semibold text-red-600">{{ $row['remaining'] }}</td>
                                <td class="px-2 py-3 text-center">
                                    @if ($row['status'] === 'Completed')
                                        <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-700">Completed</span>
                                    @elseif ($row['status'] === 'Pending')
                                        <span class="px-2 py-1 rounded text-xs bg-red-100 text-red-700">Pending</span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-gray-500 py-4">No students found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $students->links() }}
            </div>
        </div>
    @else
        <div class="mt-6 text-center text-gray-500">
            Please select a <b>Semester</b> to view student credits.
        </div>
    @endif
</x-layouts.app>
<script>
    $(document).ready(function() {
        $("#credit-filter-form").on("submit", function(e) {
            $(".error-text").remove();
            if (!$("#semester").val()) {
                e.preventDefault();
                $("#semester").closest("div").append(
                    "<span class='error-text text-red-500 text-sm'>Semester is required</span>");
            }
        });
    });
</script>
