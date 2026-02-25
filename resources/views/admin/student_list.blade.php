<x-layouts.app>
    <div class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-8 py-3">
        <h3 class="font-semibold text-primary">Student</h3>
    </div>

    @if (session()->has('sheet_error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 rounded mt-2">
            <strong>Sheet Errors:</strong>
            <p>{{ session('sheet_error') }}</p>
        </div>
    @endif

    @if (session()->has('failures'))
        <div class="text-red-700 alert alert-danger border border-danger shadow-sm mt-2">
            <h5 class="mb-3 fw-bold">
                <i class="fa fa-exclamation-triangle"></i> Import Errors
            </h5>
            <ul class="list-unstyled mb-0 mt-2">
                @foreach (session('failures') as $failure)
                    @if (!is_array($failure))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 rounded">
                            <li class="mt-2 bg-light border-start border-4 border-danger rounded">
                                <div class="mb-1">
                                    <strong class="text-danger">
                                        Row #{{ $failure->row() }}
                                    </strong>
                                </div>
                                <div>
                                    <span class="badge bg-dark me-2">
                                        {{ $failure->attribute() }}
                                    </span>
                                    @foreach ($failure->errors() as $error)
                                        <span class="badge bg-danger me-1">
                                            {{ $error }}
                                        </span>
                                    @endforeach
                                </div>
                            </li>
                        </div>
                    @else
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 rounded mt-2">
                            <strong>Import Errors:</strong>
                            <ul class="mt-2 list-disc list-inside">
                                <li>
                                    {{ implode(', ', $failure) }}
                                </li>
                            </ul>
                        </div>
                    @endif
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                showToast("{{ session('success') }}", "success",2000);
            });
        </script>
    @endif

    @if (session('error'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                showToast("{{ session('error') }}", "error",2000);
            });
        </script>
    @endif

    <div class="flex justify-end items-center gap-3 mt-3">
        <a href="{{ route('create_student') }}"
            class="flex items-center justify-center gap-1 w-[140px]
              bg-gradient-to-r from-primary to-pink-600
              text-white font-medium py-1 rounded-full">
            <i class="fa fa-plus"></i>
            Add Student
        </a>
        <a href="{{ route('students.download.template') }}" class="px-4 py-1 bg-primary text-white rounded-full">
            <i class="fa fa-download"></i> Download Template
        </a>
        <form action="{{ route('students.upload') }}" method="POST" enctype="multipart/form-data"
            class="flex items-center gap-2">
            @csrf
            <input type="file" name="file" required class="border border-gray-300 rounded px-2 py-1 text-sm">
            <button type="submit" class="px-4 py-1 bg-gradient-to-r from-primary to-pink-600 text-white rounded-full">
                <i class="fa fa-upload"></i> Upload
            </button>
        </form>
    </div>

    <h4 class="font-semibold text-gray-800 mb-4">Student Filter</h4>
    <section class="p-2 bg-white rounded-xl shadow-md mt-3">
        <div class="mt-6">
            <form method="GET" action="{{ route('student_list') }}" class="flex gap-3 mb-4">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search name / email / mobile"
                    class="border border-gray-300 rounded-full px-4 py-2 w-[300px] text-sm">
                <select name="department_id" class="border border-gray-300 rounded-full px-4 py-2 text-sm">
                    <option value="">All Departments</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}"
                            {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>

                <button type="submit"
                    class="px-5 py-2 bg-gradient-to-r from-primary to-pink-600 text-white rounded-full">
                    <i class="fa fa-search"></i> Search
                </button>

                @if (request()->hasAny(['search', 'department_id']))
                    <a href="{{ route('student_list') }}" class="px-5 py-2 bg-gray-400 text-white rounded-full">
                        Reset
                    </a>
                @endif
            </form>
        </div>
    </section>

    <h4 class="font-semibold text-gray-800 mb-4 mt-4">Promote Student</h4>
    <section class="p-2 bg-white rounded-xl shadow-md mt-3">
        <div class="mt-6">
            <form id="promoteForm" method="POST" action="{{ route('promote_student') }}" class="flex gap-3 mb-4">
                @csrf
                <select id="batchSelect" name="batch" required
                    class="w-[600px] border border-gray-300 focus:border-primary focus:ring-1 focus:ring-primary rounded-full px-4 py-2 text-sm">
                    <option value="">Select Batch</option>
                    @foreach ($batch as $b)
                        <option value="{{ $b }}">{{ $b }}</option>
                    @endforeach
                </select>
                <button type="button" onclick="openModal()"
                    class="px-6 py-2 bg-gradient-to-r from-primary to-pink-600 text-white rounded-full hover:opacity-90 transition">
                    <i class="fa fa-rocket mr-1"></i> Promote
                </button>
            </form>
        </div>
    </section>

    <!-- Promotion Modal -->
    <div id="promotionModal" class="fixed inset-0 bg-black/50 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 animate-fadeIn">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">
                Confirm Promotion
            </h2>
            <p class="text-gray-600 mb-6">
                Are you sure you want to promote students of batch
                <span id="selectedBatch" class="font-bold text-primary"></span>?
            </p>
            <div class="flex justify-end gap-3">
                <button onclick="closeModal()" class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300">
                    Cancel
                </button>
                <button onclick="submitPromotion()" class="px-4 py-2 rounded-lg bg-primary text-white hover:opacity-90">
                    Yes, Promote
                </button>
            </div>
        </div>
    </div>

    <section class="p-2">
        <div class="mt-6">
            <h4 class="font-semibold text-gray-800 mb-4">Student List</h4>
            <div class="overflow-x-auto bg-white rounded-xl shadow-md">
                <table class="w-full text-sm text-left text-gray-700 border-collapse">
                    <thead>
                        <tr class="bg-primary text-white text-sm uppercase tracking-wider">
                            <th class="px-2 py-2">ID</th>
                            <th class="px-2 py-2">Student Name</th>
                            <th class="px-2 py-2">Register Number</th>
                            <th class="px-2 py-2">Section</th>
                            <th class="px-2 py-2">Semester</th>
                            <th class="px-2 py-2">Email</th>
                            <th class="px-2 py-2">Mobile Number</th>
                            <th class="px-2 py-2">Programme</th>
                            <th class="px-2 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody" class="divide-y divide-gray-200">
                        @foreach ($student as $stud)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-2 py-3 font-medium text-gray-900">{{ $loop->iteration }}</td>
                                <td class="px-2 py-3">{{ $stud->name ?? '' }}</td>
                                <td class="px-2 py-3">{{ $stud->register_number ?? '' }}</td>
                                <td class="px-2 py-3">{{ $stud->section ?? '' }}</td>
                                <td class="px-2 py-3">{{ $stud->semester ?? '' }}</td>
                                <td class="px-2 py-3">{{ $stud->email ?? '' }}</td>
                                <td class="px-2 py-3">{{ $stud->mobile_number ?? '' }}</td>
                                <td class="px-2 py-3">{{ $stud->get_programme?->name }}</td>
                                <td class="px-2 py-3 flex justify-center gap-4">
                                    <a href="{{ route('create_student', ['student_id' => encrypt($stud->id)]) }}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">
                {{ $student->links() }}
            </div>
        </div>
    </section>
</x-layouts.app>
<script>
    function openModal() {
        let batch = document.getElementById('batchSelect').value;
  if(!batch){
      showToast("Please select a batch when promoting students", "error",2000);
      return;
  }

        document.getElementById('selectedBatch').innerText = batch;
        document.getElementById('promotionModal').classList.remove('hidden');
        document.getElementById('promotionModal').classList.add('flex');
    }

    function closeModal() {
        document.getElementById('promotionModal').classList.add('hidden');
        document.getElementById('promotionModal').classList.remove('flex');
    }

    function submitPromotion() {
        document.getElementById('promoteForm').submit();
    }
</script>
