<x-layouts.auth>
    <div class="p-5 bg-cover bg-no-repeat h-[100vh] flex items-center justify-center"
        style="background-image: url('{{ asset('/images/login.png') }}');">
        <div class="px-[1em] lg:px-[5em] xl:px-[5em] 2xl:px-[10em] w-200">
            <div class="bg-white p-10 rounded-3xl shadow-lg flex flex-col items-center text-center space-y-3">
                {{-- LOGO --}}
                <img src="{{ asset('/images/rgu_logo.jpeg') }}" alt="Logo" class="w-48 mx-auto">
                {{-- TITLE --}}
                <p class="text-2xl font-bold text-[#9F3895]">Super Admin Access</p>
                <p class="text-sm font-medium text-[#3F003E]">Mission Control Authentication Required</p>
                {{-- ADMIN IMAGE --}}
                <img src="{{ asset('/images/admin-security.png') }}" alt="Super Admin"
                    class="w-25 h-25 ">
                {{-- INSTRUCTION --}}
                <p class="text-md font-bold text-primary">Security Protocol Access</p>
                <p class="text-sm font-medium">Enter your 6-digit authentication code</p>
                {{-- FORM --}}
                <form method="POST" action="{{ route('admin.security_check') }}" class="w-full space-y-4"
                    x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    <div class="text-left">
                        <label class="block text-md font-medium text-[#3F003E]">2FA Verification Code</label>
                        <input type="text" name="verification_code" required
                            class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="000000">
                        @error('verification_code')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit"
                        class="w-full py-2 bg-gradient-to-r from-primary to-pink-500 text-white text-sm font-semibold rounded-full shadow-md hover:opacity-90 transition">
                        Complete Authentication
                    </button>
                    <div class="text-center mt-2">
                                    <a href="{{ route('admin.login') }}" class="text-xs text-purple-600 hover:underline">Go to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.auth>
