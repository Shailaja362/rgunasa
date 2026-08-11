<x-layouts.app>
    <div class="bg-[#F5E8F5] w-full h-[50px] rounded-full shadow-sm px-8 py-3 flex flex-col justify-center">
        <h3 class="font-semibold text-primary">Credit Point Assignment</h3>
    </div>
    <div class="max-w-8xl mx-auto px-4 py-8">
        <form method="POST" id="creditPointForm" action="{{ route('save_credit_point') }}"
            class="bg-white rounded-lg shadow p-4 mb-6">
            @csrf
            @if ($editData)
                <input type="hidden" name="id" value="{{ $editData->id }}">
            @endif
            <input type="hidden" name="programme_id" id="hidden_programme_id">
            <input type="hidden" name="semester" id="hidden_semester">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium"> Semester <span class="text-red-500">*</span></label>
                    <select name="semester" id="semester" {{ $editData ? 'disabled' : '' }}
                        class="semester w-full p-2 border border-gray-300 rounded-full focus:outline-none focus:ring focus:ring-primary/40 choice-select">
                        <option value="" selected disabled>Select Semester</option>
                        <option value="1"
                            {{ old('semester', $editData->semester ?? '') == '1' ? 'selected' : '' }}>1</option>
                        <option value="2"
                            {{ old('semester', $editData->semester ?? '') == '2' ? 'selected' : '' }}>2</option>
                        <option value="3"
                            {{ old('semester', $editData->semester ?? '') == '3' ? 'selected' : '' }}>3</option>
                        <option value="4"
                            {{ old('semester', $editData->semester ?? '') == '4' ? 'selected' : '' }}>4</option>
                        <option value="5"
                            {{ old('semester', $editData->semester ?? '') == '5' ? 'selected' : '' }}>5</option>
                        <option value="6"
                            {{ old('semester', $editData->semester ?? '') == '6' ? 'selected' : '' }}>6</option>
                        <option value="7"
                            {{ old('semester', $editData->semester ?? '') == '7' ? 'selected' : '' }}>7</option>
                        <option value="8"
                            {{ old('semester', $editData->semester ?? '') == '8' ? 'selected' : '' }}>8</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium">Credit Points <span class="text-red-500">*</span></label>
                    <input type="number" name="credit_points" id="credit_points"
                        value="{{ old('credit_points', $editData->credit_points ?? '') }}"
                        placeholder="Enter Credit Points"
                        class="credit_points bg-[#D9D9D9] w-full p-2 border border-gray-300 rounded-full focus:outline-none focus:ring focus:ring-primary/40">
                </div>
            </div>
            <div class="mt-6 flex justify-center gap-4">
                <button type="submit" id="saveCreditPoints"
                    class="px-6 py-2 text-sm bg-gradient-to-r from-primary to-pink-600 text-white rounded-md hover:bg-indigo-700 transition">
                    <i class="fas fa-save mr-2"></i>Save
                </button>
                <a href="{{ route('credit_point_assign') }}"
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
                        <th class="px-2 py-3 text-left text-sm font-semibold">Semester</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Credit Points</th>
                        <th class="px-2 py-3 text-left text-sm font-semibold">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($credit_points as $index => $row)
                        <tr class="border-t">
                            <td class="px-2 py-3">{{ $loop->iteration }}</td>
                            <td class="px-2 py-3">{{ $row->semester }}</td>
                            <td class="px-2 py-3">{{ $row->credit_points }}</td>
                            <td class="px-2 py-3">
                                <a href="{{ route('credit_point_assign', ['edit_id' => $row->id]) }}"
                                    class="text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-6 text-gray-500">
                                No credit points found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- Pagination --}}
        <div class="mt-4">
            {{ $credit_points->links() }}
        </div>
    </div>
</x-layouts.app>
<script>
    const saveCreditPointUrl = "{{ route('save_credit_point') }}";
    const creditPointListUrl = "{{ route('credit_point_assign') }}";
</script>
<script src="{{ asset('admin/js/credit_point.js') }}?v={{ time() }}"></script>
