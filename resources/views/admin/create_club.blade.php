<x-layouts.app>
     <div class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-6 py-4 flex justify-between items-center">
        <!-- Title & Subtitle -->
        <div class="flex flex-col">
            <h3 class="font-semibold text-primary text-lg">
               Create New Club
            </h3>
        </div>
        <!-- Back Button -->
        <a href="{{ route('club_list') }}"
            class="flex items-center text-gray-700 hover:text-primary transition-colors">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <h1 class="text-primary font-semibold mt-10 px-3">Club Information</h1>
    <p class="px-3">Basic Details about your Club</p>

    <!-- Form Start -->
    <form id="clubForm" method="POST" enctype="multipart/form-data" class="space-y-4 mt-8 px-3">
        @csrf
        @if (!empty($edit_club))
            <input type="hidden" name="club_id" value="{{ $edit_club->id }}">
        @endif

        <!-- Club Name + Code -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium">Club Name<span class="text-red-500">*</span></label>
                <input type="text" name="club_name" id="club_name" value="{{ $edit_club->name ?? '' }}"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1">
            </div>

            <div>
                <label class="block text-sm font-medium">Faculty<span class="text-red-500">*</span></label>
               <select name="faculty_id" id="faculty_id"
                    class="bg-[#D9D9D9] w-full rounded-full py-3 px-3 mt-1 choice-select">
                    <option value="">Select Faculty</option>
                    @foreach ($faculty as $fac)
                        <option value="{{ $fac->id }}"
                            {{ (!empty($edit_club) && $edit_club->faculty_id == $fac->id) ? 'selected' : '' }}>
                            {{ $fac->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium">Description</label>
                <textarea name="description" id="description"
                    class="bg-[#D9D9D9] w-full p-3 border border-gray-300 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                    rows="3">{{ $edit_club->description ?? '' }}</textarea>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-center mt-10">
            <button type="submit" id="club"
                class="px-3 bg-gradient-to-r from-primary to-pink-600 text-white font-semibold py-1 w-35 rounded-full hover:opacity-90 transition">
               <i class="fas fa-save"></i> Create Club
            </button>
        </div>

    </form>
</x-layouts.app>
<script>
    const saveClubUrl = "{{ route('save_club') }}";
    const clubListUrl = "{{ route('club_list') }}";
</script>
<script src="{{ asset('admin/js/club.js') }}?v={{ time() }}"></script>
