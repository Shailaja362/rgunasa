<x-layouts.app>
    <!-- Header -->
     <div class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-6 py-4 flex justify-between items-center">
        <!-- Title & Subtitle -->
        <div class="flex flex-col">
            <h3 class="font-semibold text-primary text-lg">
                Create New Admin
            </h3>
        </div>
        <!-- Back Button -->
        <a href="{{ route('admin_list') }}"
            class="flex items-center text-gray-700 hover:text-primary transition-colors">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back
        </a>
    </div>
    <h1 class="text-primary font-semibold mt-10 px-3">Admin Information</h1>
    <p class="px-3">Basic Details about your Admin</p>
    <!-- Form Start -->
    <form id="adminForm" method="POST" enctype="multipart/form-data" class="space-y-4 mt-8 px-3">
        @csrf
        @if (!empty($edit_admin))
            <input type="hidden" name="admin_id" value="{{ $edit_admin->id }}">
        @endif
        <!-- Admin Name + Code -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium">Admin Name<span class="text-red-500">*</span></label>
                <input type="text" name="admin_name" id="admin_name" value="{{ $edit_admin->name ?? '' }}"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1">
            </div>
            <div>
                <label class="block text-sm font-medium">Employee Code<span class="text-red-500">*</span></label>
                <input type="text" name="emp_code" id="emp_code"
                    value="{{ $edit_admin->emp_code ?? '' }}"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1">
            </div>
        </div>
        <!-- Email + Mobile -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium">Email<span class="text-red-500">*</span></label>
                <input type="text" name="email" id="email" value="{{ $edit_admin->email ?? '' }}"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1">
            </div>
            <div>
                <label class="block text-sm font-medium">Mobile Number<span class="text-red-500">*</span></label>
                <input type="text" name="mobile_number" id="mobile_number"
                    value="{{ $edit_admin->mobile_number ?? '' }}"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1">
            </div>
        </div>
        <!-- Role -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium">Role<span class="text-red-500">*</span></label>
                <select name="role_id" id="role_id" class="bg-[#D9D9D9] w-full rounded-full py-3 px-3 mt-1">
                    <option value="">Select Role</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}"
                            {{ !empty($edit_admin) && $edit_admin->role_id == $role->id ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div id="securitycodeFieldContainer">
                   @if (!empty($edit_admin) && $edit_admin->role_id == 1)
                       <label class="block mt-3 font-medium">Security Code</label>
                       <input type="text" name="security_code" id="security_code" value="{{ $edit_admin->security_code }}" placeholder="Please Enter your Security Code" class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40" required>
                   @endif
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
                            @if (!empty($edit_admin) && $edit_admin->profile_pic)
                                <img src="{{ asset('storage/' . $edit_admin->profile_pic) }}"
                                    class="mx-auto rounded-2xl w-40 h-40 object-cover" />
                                <input type="hidden" name="old_banner" value="{{ $edit_admin->profile_pic }}">
                            @endif
                        </div>
                        <!-- Upload Text -->
                        <div id="uploadText" @if (!empty($edit_admin) && $edit_admin->profile_pic) style="display:none" @endif>
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
                class="px-3 w-43 bg-gradient-to-r from-primary to-pink-600 text-white font-semibold py-1 rounded-full hover:opacity-90 transition">
              <i class="fas fa-save"></i>  Create Admin
            </button>
        </div>
    </form>
</x-layouts.app>
<script src="{{ asset('admin/js/admin.js') }}"></script>
