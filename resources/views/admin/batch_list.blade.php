<x-layouts.app>
    <div class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-8 py-3">
        <h3 class="font-semibold text-primary">Batch</h3>
    </div>
    <div class="flex justify-end">
        <a href="{{ route('create_batch') }}"
            class="px-2 w-43 mt-3 bg-gradient-to-r from-primary to-pink-600 text-white font-medium py-1 rounded-full">
            <i class="fa fa-plus" aria-hidden="true"></i>ADD Batch</a>
    </div>
    <section class="bg-white rounded-xl shadow-md p-4 mt-3">
        <div class="w-full">
            <form method="GET" action="{{ route('batch_list') }}" class="flex flex-wrap items-center gap-3">
                <div class="w-full sm:w-auto flex-1 min-w-[250px]">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search batch name here"
                        class="w-full border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <button type="submit"
                    class="px-6 py-2 bg-gradient-to-r from-primary to-pink-600 text-white text-sm rounded-full hover:opacity-90 transition">
                    <i class="fa fa-search mr-1"></i> Search
                </button>
                @if (request()->hasAny(['search']))
                    <a href="{{ route('batch_list') }}"
                        class="px-6 py-2 bg-gray-400 text-white text-sm rounded-full hover:bg-gray-500 transition">
                        Reset
                    </a>
                @endif
            </form>
        </div>
    </section>
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
                                    <button type="button" class="text-red-600 hover:text-red-800 deleteBatch"
                                        data-url="{{ route('batch_destroy', $batch->id) }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
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

    <div id="batchDeleteModal" class="fixed inset-0 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-96 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">Confirm Delete</h2>
            <p class="text-gray-600 mb-5">Are you sure you want to delete this batch?</p>
            <div class="flex justify-end gap-3">
                <button id="cancelBatchDelete" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                <button id="confirmBatchDelete" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
            </div>
        </div>
    </div>

    <script>
        let batchDeleteUrl = null;

        document.getElementById('batchTableBody').addEventListener('click', function (e) {
            const btn = e.target.closest('.deleteBatch');
            if (!btn) return;
            batchDeleteUrl = btn.dataset.url;
            document.getElementById('batchDeleteModal').classList.remove('hidden');
            document.getElementById('batchDeleteModal').classList.add('flex');
        });

        document.getElementById('cancelBatchDelete').addEventListener('click', function () {
            document.getElementById('batchDeleteModal').classList.add('hidden');
            document.getElementById('batchDeleteModal').classList.remove('flex');
        });

        document.getElementById('confirmBatchDelete').addEventListener('click', function () {
            if (!batchDeleteUrl) return;

            fetch(batchDeleteUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('batchDeleteModal').classList.add('hidden');
                    document.getElementById('batchDeleteModal').classList.remove('flex');
                    if (data.success) {
                        showToast(data.message, 'success', 2000);
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showToast(data.message || 'Failed to delete batch', 'error', 2000);
                    }
                })
                .catch(() => {
                    document.getElementById('batchDeleteModal').classList.add('hidden');
                    document.getElementById('batchDeleteModal').classList.remove('flex');
                    showToast('Something went wrong', 'error', 2000);
                });
        });
    </script>
</x-layouts.app>
