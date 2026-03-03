<x-layouts.app>
    <x-partials.navbar />
    <!-- Dashboard Header -->
    <section class="p-2 mt-3">
        @if (session('success'))
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    showToast("{{ session('success') }}", "success");
                });
            </script>
        @endif
        @if (session('error'))
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    showToast("{{ session('error') }}", "error");
                });
            </script>
        @endif
        <!-- Top Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-[#BF9CFF] rounded-2xl shadow p-5 flex flex-col justify-between">
                <p class="text-[#230060] font-medium">Total Events</p>
                <div class="flex items-center justify-between">
                    <h3 class="text-3xl font-bold text-[#230060]">{{ isset($events) ? count($events) : 0 }}</h3>
                    <img src="{{ asset('/images/calender.png') }}" alt="" class="mx-auto mt-[-40px] mb-[-40px]">
                </div>
                <div class="relative">
                    {{-- <span class="bg-white text-xs px-4 py-1 rounded-full">Available this Semester</span> --}}
                </div>
            </div>

            <div class="bg-[#FF8F6B] rounded-2xl shadow p-5 flex flex-col justify-between">
                <p class="text-[#992B07] font-medium">Register Events</p>
                <div class="flex items-center justify-between mt-2">
                    <h3 class="text-3xl font-bold text-[#992B07]">
                        {{ isset($registered_count) ? count($registered_count) : 0 }}</h3>
                    <img src="{{ asset('/images/register_event.png') }}" alt="" class="mx-auto">
                </div>
                <div class="relative">
                    <span class="bg-white text-xs px-4 py-1 rounded-full">Upcoming & Ongoing</span>
                </div>
            </div>

            <div class="bg-[#B5DAFF] rounded-2xl shadow p-5 flex flex-col justify-between">
                <p class=" text-[#0756A6] font-medium">Completed Events</p>
                <div class="flex items-center justify-between mt-2">
                    <h3 class="text-3xl font-bold text-[#0756A5]">
                        {{ isset($completed_events) ? count($completed_events) : 0 }}</h3>
                    <img src="{{ asset('/images/completed.png') }}" alt="" class="mx-auto">
                </div>
                <div class="relative">
                    {{-- <span class="bg-white text-xs px-4 py-1 rounded-full">This Academic Year</span> --}}
                </div>
            </div>

            <div class="bg-[#FFCB4B] rounded-2xl shadow p-5 flex flex-col justify-between">
                <p class="text-[#D48C28] font-medium">Certificates Earned</p>
                <div class="flex items-center justify-between mt-2">
                    <h3 class="text-3xl font-bold text-[#D48C28]">
                        {{ isset($certificate_earned) ? count($certificate_earned) : 0 }}</h3>
                    <img src="{{ asset('/images/certificates.png') }}" alt="" class="mx-auto">
                </div>
                <div class="relative">
                    {{-- <span class="bg-white text-xs px-4 py-1 rounded-full">This Academic Year</span> --}}
                </div>
            </div>
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl shadow-lg p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-indigo-700 font-medium text-medium">
                            Credit Summary
                        </p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-1">
                            @if ($config_credit->credit_points == 0)
                                0
                            @else
                                {{ $earned_credit ?? 0 }}
                            @endif /{{ isset($config_credit->credit_points) ? round($config_credit->credit_points) : '' }}
                            <span class="text-sm font-normal text-gray-500">Credits</span>
                        </h3>
                    </div>
                    <img src="{{ asset('/images/certificates.png') }}" alt="Certificates" class="w-14 h-14">
                </div>
                <div class="grid grid-cols-2 gap-4 mt-6 text-center">
                    <div class="bg-white rounded-xl p-3 shadow-sm">
                        <p class="text-xs text-gray-500">Earned</p>
                        <p class="text-lg font-bold text-green-600">
                            @if ($config_credit->credit_points == 0)
                                0
                            @else
                                {{ $earned_credit ?? 0 }}
                            @endif
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-3 shadow-sm">
                        <p class="text-xs text-gray-500">Pending</p>
                        <p class="text-lg font-bold text-red-500">
                            @if ($config_credit->credit_points == 0)
                                0
                            @else
                                {{ $pending_credit ?? 0 }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Section Header -->
        <div class="bg-[#F2E8F5] rounded-full px-5 py-3 mt-8 flex justify-between items-center">
            <h3 class="font-semibold text-primary">Events</h3>
        </div>

        <!-- Upcoming Event Section -->
        <div class="mt-6">
            <h4 class="font-semibold text-gray-800 mb-4">Upcoming Events</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($upcomingEvents as $event)
                    @foreach ($event->get_dep_events as $dept)
                        @php
                            $today = \Carbon\Carbon::now();
                            $eventDate = \Carbon\Carbon::parse($dept->event_date)->toDateString();
                            $registeredCount = \App\Models\StudentEventRegistration::where(
                                'event_schedule_id',
                                $dept->id,
                            )
                                // ->registrations()
                                ->whereHas('student', function ($query) use ($student) {
                                    $query
                                        ->where('programme_id', $student->programme_id)
                                        ->where('section', $student->section)
                                        ->where('batch', $student->batch)
                                        ->where('semester', $student->semester);
                                })
                                ->count();

                            if ($dept->is_reserve_date == 'y') {
                                $start_time = $event->reserve_start_time;
                                $end_time = $event->reserve_end_time;
                            } else {
                                $start_time = $event->start_time;
                                $end_time = $event->end_time;
                            }
                            $availableSeats = max(0, $dept->seat_count - $registeredCount);
                            $deadline = \Carbon\Carbon::parse($event->end_registration);
                            $lastRegistration = $event->registrations
                                ->where('student_id', $studentId)
                                ->where('event_schedule_id', $dept->id)
                                ->sortByDesc('registered_at')
                                ->first();
                            $cooldownActive = false;
                            $permanentBlock = false;
                            $nextAllowedDate = null;

                            if ($lastRegistration) {
                                if (empty($event->duration_months) || $event->duration_months == 0) {
                                    $permanentBlock = true;
                                }
                                if (!$permanentBlock && $event->duration_months) {
                                    $nextAllowedDate = \Carbon\Carbon::parse(
                                        $lastRegistration->registered_at,
                                    )->addMonths($event->duration_months);

                                    if ($today->lt($nextAllowedDate)) {
                                        $cooldownActive = true;
                                    }
                                }
                            }
                            $paidEventConflict = $studentRegistrations
                                ->where('event.event_type', 'paid')
                                ->where('event.event_date', $eventDate)
                                ->first();
                            $canRegister =
                                !$permanentBlock &&
                                !$cooldownActive &&
                                $availableSeats > 0 &&
                                $deadline->gte($today) &&
                                !$paidEventConflict;
                        @endphp
                        <div class="bg-white rounded-2xl shadow hover:shadow-lg transition">
                            <div class="relative">
                                <img src="{{ asset('storage/' . $event['banner_image']) }}" alt="Event"
                                    class="rounded-t-2xl w-full h-48 object-cover">
                                @if ($event['event_type'] == 'paid')
                                    <span
                                        class= "absolute top-3 right-3 bg-[#FFC31F] text-white px-3 text-sm py-1 rounded-full">
                                        Premium
                                    </span>
                                @endif
                                <span
                                    class="absolute @if ($event['event_type'] == 'paid') mt-2 top-10 @else top-3 @endif  right-3 bg-gradient-to-r from-primary to-pink-600 text-white px-3 text-sm py-1 rounded-full">
                                    <span class="text-2xl">{{ $availableSeats }} </span><span>Seats
                                        <pre> Available</span></pre>
                                    </span>
                                </span>
                                <span
                                    class="absolute bottom-3 left-3 bg-[rgba(128,128,128,0.4)] text-white text-xs px-3 py-1 rounded-full">
                                    {{ $event['title'] }}
                                </span>
                            </div>
                            <div class="p-2 details">
                                <div class="grid grid-cols-1 gap-1 md:grid-cols-4 text-xs">
                                    <div class="col-span-2 flex items-center bg-[#F2E8F5] rounded-full px-1 py-1">
                                        <i class="fa fa-clock text-primary" aria-hidden="true"></i>
                                        <p class="px-1">
                                            {{ $start_time ? \Carbon\Carbon::parse($start_time)->format('h:iA') : '-' }}
                                            -
                                            {{ $end_time ? \Carbon\Carbon::parse($end_time)->format('h:iA') : '-' }}
                                        </p>
                                    </div>
                                    <div class="col-span-2 flex items-center bg-[#F2E8F5] rounded-full px-1 py-1">
                                        <i class="fa fa-calendar text-primary" aria-hidden="true"></i>
                                        <p class="px-1">
                                            {{ \Carbon\Carbon::parse($dept->event_date)->format('F j, Y') }}</p>
                                    </div>
                                    <div class="col-span-4 flex items-center bg-[#F2E8F5] rounded-full px-1 py-1 mt-2">
                                        <i class="fa fa-map-marker text-primary" aria-hidden="true"></i>
                                        <p class="px-1">{{ $event['location'] }}</p>
                                    </div>
                                </div>
                                {{-- Status Messages --}}
                                @if ($permanentBlock)
                                    <div class="text-red-600 mt-2">
                                        You are already registered for this event.
                                    </div>
                                @elseif ($cooldownActive)
                                    <div class="text-red-600 mt-2">
                                        You can register again after
                                        <strong>{{ $nextAllowedDate->format('d M Y') }}</strong>
                                    </div>
                                @elseif ($availableSeats <= 0)
                                    <div class="text-red-600 mt-2">
                                        Seats are full.
                                    </div>
                                @elseif ($deadline->lt($today))
                                    <div class="text-red-600 mt-2">
                                        Registration deadline passed.
                                    </div>
                                @elseif ($paidEventConflict)
                                    <div class="text-red-600 mt-2">
                                        You already have a paid event on this date.
                                    </div>
                                @endif

                                {{-- Action Button --}}
                                @if ($event->event_type === 'paid')
                                    @if ($canRegister)
                                        <button class="mt-4 w-full bg-primary text-white py-2 rounded-full pay-btn"
                                            data-event-id="{{ $event->id }}"
                                            data-amount="{{ (int) $event->amount }}"
                                            data-title="{{ e($event->title) }}"
                                            data-schedule_id="{{ $dept->id }}">
                                            Pay & Register
                                        </button>
                                    @else
                                        <button disabled
                                            class="mt-4 w-full bg-gray-400 cursor-not-allowed text-white py-2 rounded-full">
                                            {{ $permanentBlock ? 'Already Registered' : 'Registration Closed' }}
                                        </button>
                                    @endif
                                @else
                                    @if ($canRegister)
                                        <button
                                            onclick="document.querySelector('.registerModal').classList.remove('hidden')"
                                            class="student_register mt-4 w-full bg-primary text-white font-medium py-2 rounded-full"
                                            data-event_id={{ $event->id }} data-schedule_id="{{ $dept->id }}">
                                            Register Now
                                        </button>
                                    @else
                                        <button disabled
                                            class="mt-4 w-full bg-gray-400 cursor-not-allowed text-white py-2 rounded-full">
                                            {{ $permanentBlock ? 'Already Registered' : 'Registration Closed' }}
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
        <!-- Ongoing Event Section -->
        <div class="mt-6">
            <h4 class="font-semibold text-gray-800 mb-4">Ongoing Events</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($ongoingEvents as $ongoing_event)
                    @foreach ($ongoing_event->get_dep_events as $department)
                        @php
                            $today = \Carbon\Carbon::now();
                            $eventDate = \Carbon\Carbon::parse($department->event_date)->toDateString();
                            $registeredCount = \App\Models\StudentEventRegistration::where(
                                'event_schedule_id',
                                $department->id,
                            )
                                ->whereHas('student', function ($query) use ($student) {
                                    $query
                                        ->where('programme_id', $student->programme_id)
                                        ->where('section', $student->section)
                                        ->where('batch', $student->batch)
                                        ->where('semester', $student->semester);
                                })
                                ->count();
                            if ($department->is_reserve_date == 'y') {
                                $start_time = $ongoing_event->reserve_start_time;
                                $end_time = $ongoing_event->reserve_end_time;
                            } else {
                                $start_time = $ongoing_event->start_time;
                                $end_time = $ongoing_event->end_time;
                            }
                            $availableSeats = max(0, $department->seat_count - $registeredCount);
                            $deadline = \Carbon\Carbon::parse($ongoing_event->end_registration);
                            $lastRegistration = $ongoing_event->registrations
                                ->where('student_id', $studentId)
                                ->where('event_schedule_id', $department->id)
                                ->sortByDesc('registered_at')
                                ->first();
                            $cooldownActive = false;
                            $permanentBlock = false;
                            $nextAllowedDate = null;
                            if ($lastRegistration) {
                                if (empty($ongoing_event->duration_months) || $ongoing_event->duration_months == 0) {
                                    $permanentBlock = true;
                                }
                                if (!$permanentBlock && $ongoing_event->duration_months) {
                                    $nextAllowedDate = \Carbon\Carbon::parse(
                                        $lastRegistration->registered_at,
                                    )->addMonths($ongoing_event->duration_months);

                                    if ($today->lt($nextAllowedDate)) {
                                        $cooldownActive = true;
                                    }
                                }
                            }
                            $paidEventConflict = $studentRegistrations
                                ->where('event.event_type', 'paid')
                                ->where('event.event_date', $eventDate)
                                ->first();
                            $canRegister =
                                !$permanentBlock &&
                                !$cooldownActive &&
                                $availableSeats > 0 &&
                                $deadline->gte($today) &&
                                !$paidEventConflict;
                        @endphp
                        <div class="bg-white rounded-2xl shadow hover:shadow-lg transition">
                            <div class="relative">
                                <img src="{{ asset('storage/' . $ongoing_event['banner_image']) }}" alt="Event"
                                    class="rounded-t-2xl w-full h-48 object-cover">
                                @if ($ongoing_event['event_type'] == 'paid')
                                    <span
                                        class= "absolute top-3 right-3 bg-[#FFC31F] text-white px-3 text-sm py-1 rounded-full">
                                        Premium
                                    </span>
                                @endif
                                <span
                                    class="absolute @if ($ongoing_event['event_type'] == 'paid') mt-2 top-10 @else top-3 @endif  right-3 bg-gradient-to-r from-primary to-pink-600 text-white px-3 text-sm py-1 rounded-full">
                                    <span class="text-2xl">{{ $availableSeats }} </span><span>Seats
                                        <pre> Available</span></pre>
                                    </span>
                                </span>
                                <span
                                    class="absolute bottom-3 left-3 bg-[rgba(128,128,128,0.4)] text-white text-xs px-3 py-1 rounded-full">
                                    {{ $ongoing_event['title'] }}
                                </span>
                            </div>
                            <div class="p-2 details">
                                <div class="grid grid-cols-1 gap-1 md:grid-cols-4 text-xs">
                                    <div class="col-span-2 flex items-center bg-[#F2E8F5] rounded-full px-1 py-1">
                                        <i class="fa fa-clock text-primary" aria-hidden="true"></i>
                                        <p class="px-1">
                                            {{ $start_time ? \Carbon\Carbon::parse($start_time)->format('h:iA') : '-' }}
                                            -
                                            {{ $end_time ? \Carbon\Carbon::parse($end_time)->format('h:iA') : '-' }}
                                        </p>
                                    </div>
                                    <div class="col-span-2 flex items-center bg-[#F2E8F5] rounded-full px-1 py-1">
                                        <i class="fa fa-calendar text-primary" aria-hidden="true"></i>
                                        <p class="px-1 m-0 p-0 text-xs">
                                            {{ \Carbon\Carbon::parse($department->event_date)->format('F j, Y') }}
                                        </p>
                                    </div>
                                    <div class="col-span-4 flex items-center bg-[#F2E8F5] rounded-full px-1 py-1 mt-2">
                                        <i class="fa fa-map-marker text-primary" aria-hidden="true"></i>
                                        <p class="px-1">{{ $ongoing_event['location'] }}</p>
                                    </div>
                                </div>
                                {{-- Status Messages --}}
                                @if ($permanentBlock)
                                    <div class="text-red-600 mt-2">
                                        You are already registered for this event.
                                    </div>
                                @elseif ($cooldownActive)
                                    <div class="text-red-600 mt-2">
                                        You can register again after
                                        <strong>{{ $nextAllowedDate->format('d M Y') }}</strong>
                                    </div>
                                @elseif ($availableSeats <= 0)
                                    <div class="text-red-600 mt-2">
                                        Seats are full.
                                    </div>
                                @elseif ($deadline->lt($today))
                                    <div class="text-red-600 mt-2">
                                        Registration deadline passed.
                                    </div>
                                @elseif ($paidEventConflict)
                                    <div class="text-red-600 mt-2">
                                        You already have a paid event on this date.
                                    </div>
                                @endif

                                {{-- Action Button --}}
                                @if ($ongoing_event->event_type === 'paid')
                                    @if ($canRegister)
                                        <button class="mt-4 w-full bg-primary text-white py-2 rounded-full pay-btn"
                                            data-event-id="{{ $ongoing_event->id }}"
                                            data-amount="{{ (int) $ongoing_event->amount }}"
                                            data-title="{{ e($ongoing_event->title) }}"
                                            data-schedule_id="{{ $department->id }}">
                                            Pay & Register
                                        </button>
                                    @else
                                        <button disabled
                                            class="mt-4 w-full bg-gray-400 cursor-not-allowed text-white py-2 rounded-full">
                                            {{ $permanentBlock ? 'Already Registered' : 'Registration Closed' }}
                                        </button>
                                    @endif
                                @else
                                    @if ($canRegister)
                                        <button
                                            onclick="document.querySelector('.registerModal').classList.remove('hidden')"
                                            class="student_register mt-4 w-full bg-primary text-white font-medium py-2 rounded-full"
                                            data-event_id={{ $ongoing_event->id }}
                                            data-schedule_id="{{ $department->id }}">
                                            Register Now
                                        </button>
                                    @else
                                        <button disabled
                                            class="mt-4 w-full bg-gray-400 cursor-not-allowed text-white py-2 rounded-full">
                                            {{ $permanentBlock ? 'Already Registered' : 'Registration Closed' }}
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
        <!-- Register Event Section -->
        <div class="mt-6">
            <h4 class="font-semibold text-gray-800 mb-4">Register Events</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($registeredEvents as $register_event)
                    @php
                        $registered = \App\Models\StudentEventRegistration::where(
                            'event_schedule_id',
                            $register_event->event_schedule_id,
                        )
                            ->whereHas('student', function ($query) use ($student) {
                                $query
                                    ->where('programme_id', $student->programme_id)
                                    ->where('section', $student->section)
                                    ->where('batch', $student->batch)
                                    ->where('semester', $student->semester);
                            })
                            ->count();
                        if ($register_event->get_event_schedule->is_reserve_date == 'y') {
                            $start_time = $register_event->event->reserve_start_time;
                            $end_time = $register_event->event->reserve_end_time;
                        } else {
                            $start_time = $register_event->event->start_time;
                            $end_time = $register_event->event->end_time;
                        }
                        $available = $register_event->get_event_schedule
                            ? $register_event->get_event_schedule->seat_count - $registered
                            : 0;

                    @endphp
                    <div class="bg-white rounded-2xl shadow hover:shadow-lg transition">
                        <div class="relative">
                            <img src="{{ asset('storage/' . $register_event->event->banner_image) }}" alt="Event"
                                class="rounded-t-2xl w-full h-48 object-cover">
                            @if ($register_event->event->event_type == 'paid')
                                <span
                                    class= "absolute top-3 right-3 bg-[#FFC31F] text-white px-3 text-sm py-1 rounded-full">
                                    Premium
                                </span>
                            @endif
                            <span
                                class="absolute @if ($register_event->event->event_type) mt-2 top-10 @else top-3 @endif  right-3 bg-gradient-to-r from-primary to-pink-600 text-white px-3 text-sm py-1 rounded-full">
                                <span class="text-2xl">{{ $available }} </span><span>Seats
                                    <pre> Available</span></pre>
                                </span>
                            </span>
                            <span
                                class="absolute bottom-3 left-3 bg-[rgba(128,128,128,0.4)] text-white text-xs px-3 py-1 rounded-full">
                                {{ $register_event->event->title }}
                            </span>
                        </div>
                        <div class="p-2 details">
                            <div class="grid grid-cols-1 gap-1 md:grid-cols-4 text-xs">
                                <div class="col-span-2 flex items-center bg-[#F2E8F5] rounded-full px-1 py-1">
                                    <i class="fa fa-clock text-primary" aria-hidden="true"></i>
                                    <p class="px-1">
                                        {{ $start_time ? \Carbon\Carbon::parse($start_time)->format('h:iA') : '-' }}
                                        -
                                        {{ $end_time ? \Carbon\Carbon::parse($end_time)->format('h:iA') : '-' }}
                                    </p>
                                </div>
                                <div class="col-span-2 flex items-center bg-[#F2E8F5] rounded-full px-1 py-1">
                                    <i class="fa fa-calendar text-primary" aria-hidden="true"></i>
                                    <p class="px-1">
                                        {{ optional($register_event->get_event_schedule)->event_date ? \Carbon\Carbon::parse($register_event->get_event_schedule->event_date)->format('F j, Y') : '-' }}
                                    </p>
                                </div>
                                <div class="col-span-4 flex items-center bg-[#F2E8F5] rounded-full px-1 py-1 mt-2">
                                    <i class="fa fa-map-marker text-primary" aria-hidden="true"></i>
                                    <p class="px-1">{{ $register_event->event->location }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @include('components.register.register-modal')
</x-layouts.app>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    window.RAZORPAY_KEY = "{{ config('services.razorpay.key') }}";
    window.username = "{{ Auth::user()->name ?? 'Student' }}";
    window.email = "{{ Auth::user()->email ?? '' }}";
</script>
<script src="{{ asset('admin/js/registration_form.js') }}?v={{ time() }}"></script>
