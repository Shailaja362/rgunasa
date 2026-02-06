<x-layouts.app>
     <div class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-6 py-4 flex justify-between items-center">
        <!-- Title & Subtitle -->
        <div class="flex flex-col">
            <h3 class="font-semibold text-primary text-lg">
                Create New Programme
            </h3>
        </div>
        <!-- Back Button -->
        <a href="{{ route('programme_list') }}"
            class="flex items-center text-gray-700 hover:text-primary transition-colors">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back
        </a>
    </div>
    <h1 class="text-primary font-semibold mt-10 px-3">Programme Information</h1>
    <p class="px-3">Basic Details about your Programme</p>
    <form id="programmeForm" method="POST" enctype="multipart/form-data" class="space-y-4 mt-8 px-3">
        @csrf
        @if (!empty($edit_programme) && isset($edit_programme))
            <input type="hidden" name="programme_id" value={{ $edit_programme->id }}>
        @endif
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium">Programme Name<span class="text-red-500">*</span></label>
                <input type="text" name="programme_name" value="{{ $edit_programme->name ?? '' }}" id="programme_name"
                    placeholder="Enter your Programme Name" class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>
            <div>
                <label class="block text-sm font-medium">Programme Code<span class="text-red-500">*</span></label>
                <input type="text" name="programme_code" value="{{ $edit_programme->code ?? '' }}" id="programme_code"
                   placeholder="Enter your Department Code" class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>
        </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6">
            <div>
                <label class="block text-sm font-medium">Department<span class="text-red-500">*</span></label>
                 <select name="department_id" id="department_id" class="bg-[#D9D9D9] w-full rounded-full py-3 px-3 focus:outline-none focus:ring focus:ring-primary/40">
                    <option value="">Select Department</option>
                    @foreach ($department as $depart)
                        <option value="{{ $depart->id }}" @if (!empty($edit_programme) && $edit_programme->department_id == $depart->id) selected @endif>
                            {{ $depart->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium">Graduate Type<span class="text-red-500">*</span></label>
               <select name="graduate_type" id="graduate_type"
                    class="bg-[#D9D9D9] w-full rounded-full py-3 px-3 focus:outline-none focus:ring focus:ring-primary/40">
                  <option value="">Select Graduate Type</option>
                    <option value="ug" @if (!empty($edit_programme) && $edit_programme->graduate_type == 'ug') selected @endif>UG</option>
                    <option value="pg" @if (!empty($edit_programme) && $edit_programme->graduate_type == 'pg') selected @endif>PG</option>

                </select>
            </div>

        </div>
        <!-- Center-Aligned Button -->
        <div class="flex justify-center mt-10">
            <button type="submit"
                class="px-10 bg-gradient-to-r from-primary to-pink-600 text-white font-semibold py-3 rounded-full hover:opacity-90 transition">
                Create Programme
            </button>
        </div>
    </form>
</x-layouts.app>
<script src="{{ asset('admin/js/department.js') }}?v={{ time() }}"></script>
