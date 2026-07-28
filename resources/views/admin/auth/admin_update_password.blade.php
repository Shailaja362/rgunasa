<x-layouts.auth>
    <div class="min-h-screen bg-cover bg-center flex items-center justify-center px-4"
        style="background-image: url('{{ asset('/images/login.png') }}');">
        <div class="max-w-6xl w-full grid grid-cols-1 md:grid-cols-12 gap-8 items-center">

            <!-- LEFT TEXT -->
            <div class="md:col-span-7 text-center md:text-left space-y-3">
                 <p class="text-3xl font-bold mb-2 text-[#FF00FD]">NASA Admin Portal</p>
                    <p class="text-lg text-white  font-semibold">
                        Embark on your journey to explore the courses and expand your
                        knowledge beyond boundaries
                    </p>
            </div>

            <!-- RIGHT FORM CARD -->
            <div class="md:col-span-5">
                <div class="bg-white p-8 md:p-10 rounded-3xl shadow-xl space-y-5">

                    <div class="flex justify-center">
                        <img src="{{ asset('/images/rgu_logo.jpeg') }}" alt="Logo" class="w-60 md:w-72">
                    </div>

                    <p class="text-center text-2xl font-semibold text-[#9F3895]">Update Password</p>

                    <form id="updatePasswordForm" method="POST" action="{{ route('admin.password.update') }}" class="space-y-4">
                        @csrf
                        <!-- Hidden email -->
                        <input type="hidden" name="email" value="{{ $email }}">

                        <!-- NEW PASSWORD -->
                        <div>
                            <label class="block text-md font-medium text-[#3F003E]">New Password</label>
                            <div class="relative">
                                <input type="password" id="password" name="password"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40 pr-10"
                                    placeholder="••••••••" required>
                                <button type="button" id="togglePassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-lg">
                                    Show
                                </button>
                            </div>
                            @error('password')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- CONFIRM PASSWORD -->
                        <div>
                            <label class="block text-md font-medium text-[#3F003E]">Confirm Password</label>
                            <div class="relative">
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40 pr-10"
                                    placeholder="••••••••" required>
                                <button type="button" id="toggleConfirmPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-lg">
                                    Show
                                </button>
                            </div>
                            <p id="passwordError" class="text-red-500 text-xs mt-1 hidden">Passwords do not match or must be at least 6 characters.</p>
                        </div>

                        <button type="submit"
                            class="w-full py-3 bg-gradient-to-r from-primary to-pink-500 text-white text-sm font-semibold rounded-full shadow-md hover:opacity-90 transition">
                            Update Password
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- JS for validation and show/hide password -->
    <script>
        // Show/Hide Password
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.textContent = type === 'password' ? 'Show' : 'Hide';
        });

        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const passwordConfirm = document.getElementById('password_confirmation');

        toggleConfirmPassword.addEventListener('click', function() {
            const type = passwordConfirm.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordConfirm.setAttribute('type', type);
            this.textContent = type === 'password' ? 'Show' : 'Hide';
        });

        // Validate passwords before submit
        document.getElementById('updatePasswordForm').addEventListener('submit', function(e) {
            const passwordValue = password.value;
            const confirmValue = passwordConfirm.value;
            const errorText = document.getElementById('passwordError');

            if(passwordValue !== confirmValue || passwordValue.length < 6) {
                e.preventDefault(); // stop form submission
                errorText.classList.remove('hidden');
            } else {
                errorText.classList.add('hidden');
            }
        });
    </script>
</x-layouts.auth>
