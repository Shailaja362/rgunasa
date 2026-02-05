<x-layouts.app>
    <!-- Header -->
    <div class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-6 py-4 flex justify-between items-center">
        <!-- Title & Subtitle -->
        <div class="flex flex-col">
            <h3 class="font-semibold text-primary text-lg">
                Create New Student
            </h3>
        </div>
        <!-- Back Button -->
        <a href="{{ route('student_list') }}"
            class="flex items-center text-gray-700 hover:text-primary transition-colors">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <h1 class="text-primary font-semibold mt-10 px-3">Student Information</h1>
    <p class="px-3">Basic Details about your Student</p>

    <!-- Form Start -->
    <form id="studentForm" method="POST" enctype="multipart/form-data" class="space-y-4 mt-8 px-3">
        @csrf
        @if (!empty($edit_student))
            <input type="hidden" name="student_id" value="{{ $edit_student->id }}">
        @endif
        <!-- Email + Mobile -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium">Student Name<span class="text-red-500">*</span></label>
                <input type="text" name="student_name" id="student_name" value="{{ $edit_student->name ?? '' }}"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>
            <div>
                <label class="block text-sm font-medium">Register Number<span class="text-red-500">*</span></label>
                <input type="text" name="register_number" id="register_number" value="{{ $edit_student->register_number ?? '' }}"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>
            <div>
                <label class="block text-sm font-medium">Date of Birth</label>
                <input type="date" name="date_of_birth" id="date_of_birth"
                    value="{{ $edit_student->date_of_birth ?? '' }}"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>
            <div>
                <label class="block text-sm font-medium">Email<span class="text-red-500">*</span></label>
                <input type="text" name="email" id="email" value="{{ $edit_student->email ?? '' }}"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>
            <div>
                <label class="block text-sm font-medium">Mobile Number<span class="text-red-500">*</span></label>
                <input type="text" name="mobile_number" id="mobile_number"
                    value="{{ $edit_student->mobile_number ?? '' }}"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>
             <div>
                <label class="block text-sm font-medium">Department<span class="text-red-500">*</span></label>
                <select name="department_id" id="department_id" class="bg-[#D9D9D9] w-full rounded-full py-3 px-3 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
                    <option value="">Select Department</option>
                    @foreach ($department as $depart)
                        <option value="{{ $depart->id }}"
                            {{ !empty($edit_student) && $edit_student->department_id == $depart->id ? 'selected' : '' }}>
                            {{ $depart->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium">Programme<span class="text-red-500">*</span></label>
                <select name="programme_id" id="programme_id" class="bg-[#D9D9D9] w-full rounded-full py-3 px-3 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
                    <option value="">Select Programme</option>
                    @foreach ($programme as $prog)
                        <option value="{{ $prog->id }}"
                            {{ !empty($edit_student) && $edit_student->programme_id == $prog->id ? 'selected' : '' }}>
                            {{ $prog->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium"> Gender <span class="text-red-500">*</span></label>
                <select name="gender" id="gender"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
                    <option value="" selected disabled>Select Gender</option>
                    <option value="m"  {{  $edit_student?->gender == "m" ? 'selected' : '' }}>Male</option>
                    <option value="f"  {{  $edit_student?->gender == "f" ? 'selected' : '' }}>FeMale</option>
                    <option value="o" {{  $edit_student?->gender == "o" ? 'selected' : '' }}>Others</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium"> Section <span class="text-red-500">*</span></label>
                <select name="section" id="section" class="w-full bg-[#D9D9D9] rounded-full px-4 py-3 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
                    <option value="" selected disabled>Select Section</option>
                    <option value="a" {{  $edit_student?->section == "a" ? 'selected' : '' }}>A</option>
                    <option value="b" {{  $edit_student?->section == "b" ? 'selected' : '' }}>B</option>
                    <option value="c" {{  $edit_student?->section == "c" ? 'selected' : '' }}>C</option>
                    <option value="d" {{  $edit_student?->section == "d" ? 'selected' : '' }}>D</option>
                    <option value="r" {{  $edit_student?->section == "r" ? 'selected' : '' }}>R</option>
                </select>
            </div>
        </div>
        <!-- Department + Designation -->
        <!-- IMAGE UPLOAD START -->
        <div class="grid grid-cols-1">
            <div class="bg-[#F0F0F0] rounded-3xl p-10 text-center relative">
                <div id="uploadBox" class="space-y-8">
                    <div id="dropArea"
                        class="border-2 border-dashed border-primary rounded-2xl p-8 relative cursor-pointer">
                        <!-- Preview Area -->
                        <div id="previewArea" class="flex justify-center">
                            @if (!empty($edit_student) && $edit_student->profile_pic)
                                <img src="{{ asset('storage/' . $edit_student->profile_pic) }}"
                                    class="mx-auto rounded-2xl w-40 h-40 object-cover" />
                                <input type="hidden" name="old_banner" value="{{ $edit_student->profile_pic }}">
                            @endif
                        </div>
                        <!-- Upload Text -->
                        <div id="uploadText" @if (!empty($edit_student) && $edit_student->profile_pic) style="display:none" @endif>
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
            <button type="submit"
                class="px-10 bg-gradient-to-r from-primary to-pink-600 text-white font-semibold py-3 rounded-full hover:opacity-90 transition">
                <i class="fas fa-save"></i> Create Student
            </button>
        </div>

    </form>
</x-layouts.app>
<script src="{{ asset('admin/js/student.js') }}"></script>
