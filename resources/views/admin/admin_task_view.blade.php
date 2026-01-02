<x-layouts.app>
    <!-- Header -->
    <div class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-6 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <h3 class="font-semibold text-primary text-lg sm:text-xl">Task Details</h3>
        <a href="{{ route('event_list') }}"
           class="flex items-center text-gray-700 hover:text-primary transition-colors">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <section class="mt-6 p-4 sm:p-6 bg-white rounded-2xl shadow-sm">
        <h3 class="font-semibold text-primary text-lg mb-4">Assigned Task Details</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <!-- Assigned Admin -->
            <div class="col-span-2 sm:col-span-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Assigned Admin</label>
                <input type="text" value="{{ $task->get_admin->name ?? '-' }}" disabled
                       class="bg-gray-100 w-full rounded-full px-4 py-3 cursor-not-allowed focus:outline-none">
            </div>

            <!-- Task Title -->
            <div class="col-span-2 sm:col-span-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Task Title</label>
                <input type="text" value="{{ $task->title ?? '-' }}" disabled
                       class="bg-gray-100 w-full rounded-full px-4 py-3 cursor-not-allowed focus:outline-none">
            </div>

            <!-- Description -->
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea disabled rows="4"
                          class="bg-gray-100 w-full p-4 border border-gray-300 rounded-2xl cursor-not-allowed focus:outline-none">{{ $task->description ?? '-' }}</textarea>
            </div>

            <!-- Priority -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Priority Level</label>
                <input type="text" value="{{ ucfirst($task->priority ?? '-') }}" disabled
                       class="bg-gray-100 w-full rounded-full px-4 py-3 cursor-not-allowed focus:outline-none">
            </div>

            <!-- Deadline -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Deadline Date</label>
                <input type="text"
                       value="{{ !empty($task->deadline_date) ? \Carbon\Carbon::parse($task->deadline_date)->format('M d, Y h:i A') : '-' }}"
                       disabled
                       class="bg-gray-100 w-full rounded-full px-4 py-3 cursor-not-allowed focus:outline-none">
            </div>

            <!-- Uploaded Images -->
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Uploaded Images</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 mt-2">
                    @if (!empty($task->get_task_images))
                        @foreach ($task->get_task_images as $img)
                            <div class="relative group border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow">
                                <img src="{{ asset('storage/' . $img['file_path']) }}"
                                     class="w-full h-32 object-cover" alt="{{ $img['filename'] }}">
                                <p class="absolute bottom-0 left-0 w-full bg-black/50 text-white text-xs text-center py-1 truncate">
                                    {{ $img['filename'] }}
                                </p>
                            </div>
                        @endforeach
                    @else
                        <p class="text-gray-500 col-span-2">No images uploaded.</p>
                    @endif
                </div>
            </div>

            <!-- Status -->
            <div class="col-span-2 sm:col-span-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <input type="text" value="{{ ucfirst($task->status ?? '-') }}" disabled
                       class="bg-gray-100 w-full rounded-full px-4 py-3 cursor-not-allowed focus:outline-none">
            </div>
        </div>
    </section>
</x-layouts.app>
