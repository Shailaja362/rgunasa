<x-layouts.auth>
    <div class="p-5 bg-cover no-repeat h-[100vh] flex flex-col justify-center "
        style="background-image: url('{{ asset('/images/login.png') }}');">
        <div class="px-[1em] lg:px-[5em] xl:px-[5em] 2xl:px-[10em]">
            <div class="grid grid-cols-12 gap-3">
                {{-- LEFT COLUMN --}}
                <div class="col-span-12 md:col-span-7 text-center my-auto">
                    <p class="text-3xl font-bold mb-2 text-[#FF00FD]">NON ACADEMIC STUDENT ACTIVITY</p>
                    <p class="text-2xl font-semibold text-[#FF00FD] mb-4">NASA Student Portal</p>
                    <p class="text-lg text-white  font-semibold">
                        Embark on your journey to explore the courses and expand your
                        knowledge beyond boundaries
                    </p>
                </div>
                {{-- RIGHT COLUMN --}}
                <div class="col-span-12 md:col-span-5">
                    <div class="bg-white p-10 rounded-3xl space-y-3">
                        <img src="{{ asset('/images/rgu_logo.jpeg') }}" alt="Logo" class="w-80 mx-auto">
                        <p class="text-center text-2xl font-medium text-[#9F3895]">
                            LOGIN
                        </p>
                        <p class="text-center text-sm font-medium text-[#3F003E]">
                            Login to stay connnected
                        </p>
                        {{-- FORM SEGMENT --}}
                        <form method="POST" action="{{ route('student.login') }}" class="space-y-3"
                            x-data="{ show: false, loading: false }" @submit="loading = true">
                            @csrf
                            <label class="block text-md font-medium text-[#3F003E]">Email</label>
                            <input type="email" name="email" required
                                class="w-full mt-1 px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                                placeholder="you@example.com">
                            @error('email')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                            <div>
                                <label class="block text-md font-medium text-[#3F003E]">Password</label>
                                <div class="relative">
                                    <input :type="show ? 'text' : 'password'" name="password"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 mt-1
                             focus:outline-none focus:ring focus:ring-primary/40 pr-10"
                                        placeholder="••••••••" />
                                    <button type="button" @click="show = !show"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-lg">
                                        <i x-show="!show" class="fa fa-eye-slash" aria-hidden="true"></i>
                                        <i x-show="show" class="fa fa-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex flex-col justify-center align-center">
                                {{-- Login Button --}}
                                <div class="flex flex-col justify-center align-center">
                                    <button type="submit"
                                        class="w-full lg:w-auto px-10 py-2 bg-gradient-to-r from-primary to-pink-500 text-white text-sm font-semibold rounded-full shadow-md hover:opacity-90 transition duration-200">
                                        Login
                                    </button>
                                </div>

                                <div class="text-center mt-2">
                                    <a href="{{ route('student.password.forgot') }}"
                                        class="text-xs text-purple-600 hover:underline">Forget
                                        Password?</a>
                                </div>
                                <div class="text-center mt-1">
                                    <span class="text-xs text-gray-700">Not registered yet?</span>
                                    <a href="{{ route('student.register_student') }}"
                                        class="text-xs text-purple-700 font-semibold hover:underline ml-1">
                                        Create an account
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.auth>
