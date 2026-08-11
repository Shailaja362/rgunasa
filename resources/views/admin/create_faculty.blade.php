<x-layouts.app>
     <style>
        .choices__inner {
            background-color: #D9D9D9 !important;
            border-radius: 9999px !important;
            border: none !important;
            min-height: 48px !important;
            padding: 8px 16px !important;
        }

        .choices.is-focused .choices__inner {
            border: none !important;
            box-shadow: none !important;
        }

        .choices__list--dropdown {
            border-radius: 12px !important;
            border: 1px solid #ddd !important;
        }

        .choices__item {
            font-size: 14px;
        }

        .choices__inner:focus {
            outline: none !important;
        }

        .choices[data-type*="select-one"] .choices__inner {
            padding-bottom: 8px !important;
        }
    </style>
    <!-- Header -->
     <div class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-6 py-4 flex justify-between items-center">
        <!-- Title & Subtitle -->
        <div class="flex flex-col">
            <h3 class="font-semibold text-primary text-lg">
                Create New Faculty
            </h3>
        </div>
        <!-- Back Button -->
        <a href="{{ route('faculty_list') }}"
            class="flex items-center text-gray-700 hover:text-primary transition-colors">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <h1 class="text-primary font-semibold mt-10 px-3">Faculty Information</h1>
    <p class="px-3">Basic Details about your Faculty</p>

    <!-- Form Start -->
    <form id="facultyForm" method="POST" enctype="multipart/form-data" class="space-y-4 mt-8 px-3">
        @csrf

        @if (!empty($edit_faculty))
            <input type="hidden" name="faculty_id" value="{{ $edit_faculty->id }}">
        @endif

        <!-- Faculty Name + Code -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium">Faculty Name<span class="text-red-500">*</span></label>
                <input type="text" name="faculty_name" id="faculty_name" value="{{ $edit_faculty->name ?? '' }}"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1">
            </div>

            <div>
                <label class="block text-sm font-medium">Faculty Code<span class="text-red-500">*</span></label>
                <input type="text" name="faculty_code" id="faculty_code"
                    value="{{ $edit_faculty->faculty_code ?? '' }}"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1">
            </div>
        </div>

        <!-- Email + Mobile -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium">Email<span class="text-red-500">*</span></label>
                <input type="text" name="email" id="email" value="{{ $edit_faculty->email ?? '' }}"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1">
            </div>

            <div>
                <label class="block text-sm font-medium">Mobile Number<span class="text-red-500">*</span></label>
                <input type="text" name="mobile_number" id="mobile_number"
                    value="{{ $edit_faculty->mobile_number ?? '' }}"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1">
            </div>
        </div>

        <!-- Department + Designation -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium">Department<span class="text-red-500">*</span></label>
                <select name="department_id" id="department_id" class="bg-[#D9D9D9] w-full rounded-full py-3 px-3 mt-1 choice-select">
                    <option value="">Select Department</option>
                    @foreach ($department as $depart)
                        <option value="{{ $depart->id }}"
                            {{ !empty($edit_faculty) && $edit_faculty->department_id == $depart->id ? 'selected' : '' }}>
                            {{ $depart->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium">Designation<span class="text-red-500">*</span></label>
                <select name="designation_id" id="designation_id"
                    class="bg-[#D9D9D9] w-full rounded-full py-3 px-3 mt-1 choice-select">
                    <option value="">Select Designation</option>
                    @foreach ($designation as $desig)
                        <option value="{{ $desig->id }}"
                            {{ !empty($edit_faculty) && $edit_faculty->designation_id == $desig->id ? 'selected' : '' }}>
                            {{ $desig->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- IMAGE UPLOAD START -->
        <div class="grid grid-cols-1">
            <div class="bg-[#F0F0F0] rounded-3xl p-10 text-center relative">
                <div id="uploadBox" class="space-y-8">
                    <div id="dropArea"
                        class="border-2 border-dashed border-primary rounded-2xl p-8 relative cursor-pointer">
                        <!-- Preview Area -->
                        <div id="previewArea" class="flex justify-center">
                            @if (!empty($edit_faculty) && $edit_faculty->profile_pic)
                                <img src="{{ asset('storage/' . $edit_faculty->profile_pic) }}"
                                    class="mx-auto rounded-2xl w-40 h-40 object-cover" />
                                <input type="hidden" name="old_banner" value="{{ $edit_faculty->profile_pic }}">
                            @endif
                        </div>
                        <!-- Upload Text -->
                        <div id="uploadText" @if (!empty($edit_faculty) && $edit_faculty->profile_pic) style="display:none" @endif>
                            <img src="{{ asset('/images/upload.png') }}" class="mx-auto w-14 mb-3" />
                            <p class="text-primary font-semibold">Upload your profile image</p>
                            <p class="text-primary text-sm mt-2">PNG, JPG up to 5MB</p>
                        </div>
                        <!-- File Input -->
                        <input type="file" id="fileInput" name="banner_image" accept="image/*" class="hidden" />
                    </div>
                </div>
            </div>
        </div>
        <!-- IMAGE UPLOAD END -->

        <!-- Submit -->
        <div class="flex justify-center mt-10">
            <button type="submit" id="faculty"
                class="px-10 bg-gradient-to-r from-primary to-pink-600 text-white font-semibold py-3 rounded-full hover:opacity-90 transition">
                Create Faculty
            </button>
        </div>

    </form>
</x-layouts.app>
<script>
    const saveFacultyUrl = "{{ route('save_faculty') }}";
    const facultyListUrl = "{{ route('faculty_list') }}";
</script>
<script src="{{ asset('admin/js/faculty.js') }}?v={{ time() }}"></script>
<script src="{{ asset('admin/js/common.js') }}?v={{ time() }}"></script>
