<!DOCTYPE html>
<html lang="en">

<head>
    <x-partials.header />
</head>

<body>
    <div>
        <div class="px-[2em] lg:px-[5em] xl:px-[5em] 2xl:px-[10em]">
            <p class="px-3 mt-5 font-semibold text-primary">Basic Details about the Student</p>
            <form id="studentregisterForm" action="" method="POST" enctype="multipart/form-data" class="mt-5 px-3">
                @csrf
                <!-- Email + Mobile -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium">Student Name <span class="text-red-500">*</span></label>
                        <input type="text" name="student_name" id="student_name"
                            value="{{ old('student_name', $edit_student->name ?? '') }}"
                            class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
                        @error('student_name')
                            <small class="text-red-500">{{ $message }}</small>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Register Number<span
                                class="text-red-500">*</span></label>
                        <input type="text" name="register_number" id="register_number"
                            value="{{ $edit_student->register_number ?? '' }}"
                            class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Date of Birth</label>
                        <input type="date" name="date_of_birth" id="date_of_birth"
                            value="{{ old('date_of_birth', $edit_student->date_of_birth ?? '') }}"
                            class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
                        @error('date_of_birth')
                            <small class="text-red-500">{{ $message }}</small>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Email <span class="text-red-500">*</span></label>
                        <input type="text" name="email" id="email"
                            value="{{ old('email', $edit_student->email ?? '') }}"
                            class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
                        @error('email')
                            <small class="text-red-500">{{ $message }}</small>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Mobile Number <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="mobile_number" id="mobile_number"
                            value="{{ old('mobile_number', $edit_student->mobile_number ?? '') }}"
                            class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
                        @error('mobile_number')
                            <small class="text-red-500">{{ $message }}</small>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium"> Gender <span class="text-red-500">*</span></label>
                        <select name="gender" id="gender"
                            class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 focus:outline-none focus:ring focus:ring-primary/40">
                            <option value="" selected disabled>Select Gender</option>
                            <option value="m">Male</option>
                            <option value="f">FeMale</option>
                            <option value="o">Others</option>
                        </select>
                        @error('gender')
                            <small class="text-red-500">{{ $message }}</small>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Department <span class="text-red-500">*</span></label>
                        <select name="department_id" id="department_id"
                            class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
                            <option value="">Select Department</option>
                            @foreach ($department as $depart)
                                <option value="{{ $depart->id }}"
                                    {{ old('department_id', $edit_student->department_id ?? '') == $depart->id ? 'selected' : '' }}>
                                    {{ $depart->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <small class="text-red-500">{{ $message }}</small>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Programme <span class="text-red-500">*</span></label>
                        <select name="programme_id" id="programme_id"
                            class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
                            <option value="">Select Programme</option>
                            @foreach ($programme as $prog)
                                <option value="{{ $prog->id }}"
                                    {{ old('programme_id', $edit_student->programme_id ?? '') == $prog->id ? 'selected' : '' }}>
                                    {{ $prog->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('programme_id')
                            <small class="text-red-500">{{ $message }}</small>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium"> Section <span class="text-red-500">*</span></label>
                        <select name="section" id="section"
                            class="w-full bg-[#D9D9D9] rounded-full px-4 py-3 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
                            <option value="" selected disabled>Select Section</option>
                            <option value="a">A</option>
                            <option value="b">B</option>
                            <option value="c">C</option>
                            <option value="d">D</option>
                            <option value="r">R</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Profile Image</label>
                        <div class="bg-[#F0F0F0] rounded-3xl p-10 text-center">
                            <div id="dropArea"
                                class="border-2 border-dashed border-primary rounded-2xl p-8 cursor-pointer">
                                <div id="previewArea" class="flex justify-center mb-3">
                                    @if (!empty($edit_student) && $edit_student->profile_pic)
                                        <img src="{{ asset('storage/' . $edit_student->profile_pic) }}"
                                            class="mx-auto rounded-2xl w-40 h-40 object-cover">
                                        <input type="hidden" name="old_banner"
                                            value="{{ $edit_student->profile_pic }}">
                                    @endif
                                </div>

                                <div id="uploadText" @if (!empty($edit_student) && $edit_student->profile_pic) style="display:none;" @endif>
                                    <img src="{{ asset('/images/upload.png') }}" class="mx-auto w-14 mb-3" />
                                    <p class="text-primary font-semibold">Upload your profile image</p>
                                    <p class="text-primary text-sm mt-2">PNG, JPG up to 5MB</p>
                                </div>

                                <input type="file" id="fileInput" name="banner_image" accept="image/*"
                                    class="hidden">
                            </div>
                        </div>
                        @error('banner_image')
                            <small class="text-red-500">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <!-- Profile Image Upload -->
                <!-- Submit -->
                <div class="flex flex-col sm:flex-row justify-center gap-4 p-6">
                    <!-- Register Button -->
                    <button type="submit"
                        class="w-full sm:w-auto px-10 md:px-14 bg-gradient-to-r from-primary to-pink-600 text-white font-semibold py-3 rounded-full hover:opacity-90 transition flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i>
                        Register Student
                    </button>

                    <!-- Go to Login Button -->
                    <a href="{{ route('student.login') }}"
                        class="w-full sm:w-auto px-10 md:px-14 border-2 bg-primary border-primary text-white font-semibold py-3 rounded-full hover:bg-primary  transition flex items-center justify-center gap-2">
                        <i class="fas fa-sign-in-alt"></i>
                        Go to Login
                    </a>
                </div>
            </form>
            <div id="toast-container" class="fixed top-5 right-5 space-y-2 z-50"></div>
        </div>
    </div>

    <x-partials.scripts />
</body>

</html>
<script src="{{ asset('admin/js/student.js') }}?v={{ time() }}"></script>
