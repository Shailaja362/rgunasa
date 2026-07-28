<x-layouts.auth>
    <div class="min-h-screen bg-cover bg-center flex items-center justify-center px-4"
        style="background-image: url('{{ asset('/images/login.png') }}');">

        <div class="max-w-6xl w-full grid grid-cols-1 md:grid-cols-12 gap-8 items-center">

            <!-- LEFT TEXT -->
            <div class="md:col-span-7 text-center md:text-left space-y-3">
                <p class="text-3xl font-bold text-[#FF00FD]">NON ACADEMIC STUDENT ACTIVITY</p>
                <p class="text-2xl font-semibold text-[#FF00FD]">NASA Student Portal</p>
                <p class="text-lg text-white font-semibold leading-relaxed max-w-lg">
                    Embark on your journey to explore the courses and expand your knowledge beyond boundaries
                </p>
            </div>

            <!-- RIGHT FORM CARD -->
            <div class="md:col-span-5">
                <div class="bg-white p-8 md:p-10 rounded-3xl shadow-xl space-y-5">
                    <div class="flex justify-center">
                        <img src="{{ asset('/images/rgu_logo.jpeg') }}" alt="Logo" class="w-60 md:w-72">
                    </div>
                    <p class="text-center text-2xl font-semibold text-[#9F3895]">Verify Email</p>
                    <form  method="POST" action="{{ route('student.password.verify') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-md font-medium text-[#3F003E]">Email</label>
                            <input type="email" name="email" id="email" required
                                class="w-full mt-1 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                placeholder="you@example.com">
                            @error('email')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit"
                            class="w-full py-3 bg-gradient-to-r from-primary to-pink-500 text-white text-sm font-semibold rounded-full shadow-md hover:opacity-90 transition">
                            Next
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.auth>

