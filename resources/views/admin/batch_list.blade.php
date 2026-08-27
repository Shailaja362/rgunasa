<x-layouts.app>
    <div class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-8 py-3">
        <h3 class="font-semibold text-primary">Batch</h3>
    </div>
    <div class="flex justify-end">
        <a href="{{ route('create_batch') }}"
            class="px-2 w-43 mt-3 bg-gradient-to-r from-primary to-pink-600 text-white font-medium py-1 rounded-full">
            <i class="fa fa-plus" aria-hidden="true"></i>ADD Batch</a>
    </div>
    <section class="p-2 mt-3">
        <div class="mt-6">
            <h4 class="font-semibold text-gray-800 mb-4">Batch List</h4>

            <div class="overflow-x-auto bg-white rounded-xl shadow-md">
                <table class="w-full text-sm text-left text-gray-700 border-collapse">
                    <thead>
                        <tr class="bg-primary text-white text-sm uppercase tracking-wider">
                            <th class="px-3 py-2">ID</th>
                            <th class="px-3 py-2">Batch Name</th>
                            <th class="px-3 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody id="batchTableBody" class="divide-y divide-gray-200">
                        @foreach ($batches as $batch)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3">{{ $batch->name ?? '' }}</td>
                                <td class="px-4 py-3 flex justify-center gap-4">
                                    <a href="{{ route('create_batch', ['batch_id' => encrypt($batch->id)]) }}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">
                {{ $batches->links() }}
            </div>
        </div>
    </section>
</x-layouts.app>
