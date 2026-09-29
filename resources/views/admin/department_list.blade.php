<x-layouts.app>
    <div class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-8 py-3">
        <h3 class="font-semibold text-primary">Department</h3>
    </div>
    <div class="flex justify-end">
        <a href="{{ route('create_department') }}"
            class="px-2 w-43 mt-3 bg-gradient-to-r from-primary to-pink-600 text-white font-medium py-1 rounded-full">
            <i class="fa fa-plus" aria-hidden="true"></i>ADD Department</a>
    </div>
    <section class="bg-white rounded-xl shadow-md p-4 mt-3">
        <div class="w-full">
            <form method="GET" action="{{ route('department_list') }}" class="flex flex-wrap items-center gap-3">
                <div class="w-full sm:w-auto flex-1 min-w-[250px]">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search name/code here"
                        class="w-full border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <button type="submit"
                    class="px-6 py-2 bg-gradient-to-r from-primary to-pink-600 text-white text-sm rounded-full hover:opacity-90 transition">
                    <i class="fa fa-search mr-1"></i> Search
                </button>
                @if (request()->hasAny(['search']))
                    <a href="{{ route('department_list') }}"
                        class="px-6 py-2 bg-gray-400 text-white text-sm rounded-full hover:bg-gray-500 transition">
                        Reset
                    </a>
                @endif
            </form>
        </div>
    </section>
    <section class="p-2 mt-3">
        <div class="mt-6">
            <h4 class="font-semibold text-gray-800 mb-4">Department List</h4>

            <div class="overflow-x-auto bg-white rounded-xl shadow-md">
                <table class="w-full text-sm text-left text-gray-700 border-collapse">
                    <thead>
                        <tr class="bg-primary text-white text-sm uppercase tracking-wider">
                            <th class="px-3 py-2">ID</th>
                            <th class="px-3 py-2">Department Name</th>
                            <th class="px-3 py-2">Department Code</th>
                            <th class="px-3 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody id="departmentTableBody" class="divide-y divide-gray-200">
                        @foreach ($departments as $department)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3">{{ $department->name ?? '' }}</td>
                                <td class="px-4 py-3">{{ $department->code }}</td>
                                <td class="px-4 py-3 flex justify-center gap-4">
                                    <a
                                        href="{{ route('create_department', ['department_id' => encrypt($department->id)]) }}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    @if (!empty(session()->get('super_admin')))
                                        <button type="button" class="text-red-600 hover:text-red-800 deleteRecord"
                                            data-url="{{ route('department_destroy', $department->id) }}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">
                {{ $departments->links() }}
            </div>
        </div>
    </section>
    @if (!empty(session()->get('super_admin')))
        <x-partials.delete-modal label="department" />
    @endif
</x-layouts.app>
