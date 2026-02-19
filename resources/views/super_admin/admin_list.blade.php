<x-layouts.app>
    <div class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-8 py-3">
        <h3 class="font-semibold text-primary">Admin</h3>
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
                        <li class="mb-3 p-3 bg-light border-start border-4 border-danger rounded">
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
                showToast("{{ session('success') }}", "success");
            });
        </script>
    @endif

    <div class="flex justify-end items-center gap-3 mt-3">
        <!-- Add Admin -->
        <a href="{{ route('create_admin') }}"
            class="flex items-center justify-center gap-1 w-[140px]
              bg-gradient-to-r from-primary to-pink-600
              text-white font-medium py-1 rounded-full">
            <i class="fa fa-plus"></i>
            Add Admin
        </a>
        <!-- Download Template -->
        <a href="{{ route('admin.download.template') }}" class="px-4 py-1 bg-primary text-white rounded-full">
            <i class="fa fa-download"></i> Download Template
        </a>
        <!-- Upload Admin -->
        <form action="{{ route('admin.upload') }}" method="POST" enctype="multipart/form-data"
            class="flex items-center gap-2">
            @csrf
            <input type="file" name="file" required class="border border-gray-300 rounded px-2 py-1 text-sm">
            <button type="submit" class="px-4 py-1 bg-gradient-to-r from-primary to-pink-600 text-white rounded-full">
                <i class="fa fa-upload"></i> Upload
            </button>
        </form>
    </div>
    <h4 class="font-semibold text-gray-800 mb-4">Admin Filter</h4>
    <section class="p-2 bg-white rounded-xl shadow-md mt-3">
        <div class="mt-6">
            <form method="GET" action="{{ route('admin_list') }}" class="flex gap-3 mb-4">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search name / email / mobile"
                    class="border border-gray-300 rounded-full px-4 py-2 w-[500px] text-sm">
                <button type="submit"
                    class="px-5 py-2 bg-gradient-to-r from-primary to-pink-600 text-white rounded-full">
                    <i class="fa fa-search"></i> Search
                </button>
                @if (request()->hasAny(['search']))
                    <a href="{{ route('admin_list') }}" class="px-5 py-2 bg-gray-400 text-white rounded-full">
                        Reset
                    </a>
                @endif
            </form>
        </div>
    </section>
    <section class="p-2 mt-3">
        <div class="mt-6">
            <h4 class="font-semibold text-gray-800 mb-4">Admin List</h4>
            <div class="overflow-x-auto bg-white rounded-xl shadow-md">
                <table class="w-full text-sm text-left text-gray-700 border-collapse">
                    <thead>
                        <tr class="bg-primary text-white text-sm uppercase tracking-wider">
                            <th class="px-2 py-2">ID</th>
                            <th class="px-2 py-2">Employee Code</th>
                            <th class="px-2 py-2">Admin Name</th>
                            <th class="px-2 py-2">Role</th>
                            <th class="px-2 py-2">Mobile Number</th>
                            <th class="px-2 py-2">Department</th>
                            <th class="px-2 py-2">Designation</th>
                            <th class="px-2 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody id="adminTableBody" class="divide-y divide-gray-200">
                        @foreach ($admins as $admin)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-2 py-3 font-medium text-gray-900">{{ $loop->iteration }}</td>
                                <td class="px-2 py-3">{{ $admin->emp_code }}</td>
                                <td class="px-2 py-3">{{ $admin->name ?? '' }}</td>
                                <td class="px-2 py-3">{{ $admin->get_role->name ?? '' }}</td>
                                <td class="px-2 py-3">{{ $admin->mobile_number ?? '' }}</td>
                                <td class="px-2 py-3">{{ $admin->get_department?->name ?? '' }}</td>
                                <td class="px-2 py-3">{{ $admin->get_designation?->name ?? '' }}</td>
                                <td class="px-2 py-3 flex justify-center gap-4">
                                    <a href="{{ route('create_admin', ['admin_id' => encrypt($admin->id)]) }}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">
                {{ $admins->links() }}
            </div>
        </div>
    </section>
</x-layouts.app>
