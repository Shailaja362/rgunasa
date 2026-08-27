<x-layouts.app>
     <div class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-6 py-4 flex justify-between items-center">
        <!-- Title & Subtitle -->
        <div class="flex flex-col">
            <h3 class="font-semibold text-primary text-lg">
               Create New Batch
            </h3>
        </div>
        <!-- Back Button -->
        <a href="{{ route('batch_list') }}"
            class="flex items-center text-gray-700 hover:text-primary transition-colors">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back
        </a>
    </div>
    <h1 class="text-primary font-semibold mt-10 px-3">Batch Information</h1>
    <p class="px-3">Basic Details about your Batch</p>
    <form id="batchForm" method="POST" enctype="multipart/form-data" class="space-y-4 mt-8 px-3">
        @csrf
        @if (!empty($edit_batch) && isset($edit_batch))
            <input type="hidden" name="batch_id" value={{ $edit_batch->id }}>
        @endif
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium">Batch Name<span class="text-red-500">*</span></label>
                <input type="text" name="batch_name" value="{{ $edit_batch->name ?? '' }}" id="batch_name"
                    placeholder="e.g, 2025-2029" class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>
        </div>
        <!-- Center-Aligned Button -->
        <div class="flex justify-center mt-10">
            <button type="submit" id="batch"
                class="px-10 bg-gradient-to-r from-primary to-pink-600 text-white font-semibold py-3 rounded-full hover:opacity-90 transition">
                Create Batch
            </button>
        </div>
    </form>
</x-layouts.app>
<script>
    const saveBatchUrl = "{{ route('save_batch') }}";
    const batchListUrl = "{{ route('batch_list') }}";
</script>
<script src="{{ asset('admin/js/batch.js') }}?v={{ time() }}"></script>
