<x-layouts.app>

    {{-- ================= HEADER ================= --}}
    <div class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-8 py-3">
        <h3 class="font-semibold text-primary">Events</h3>
    </div>

    {{-- ================= TABS ================= --}}
    <div class="px-5 py-3 mt-4">
        <div class="flex space-x-4 text-gray-700 font-medium">
            <span id="upcoming-tab" class="cursor-pointer bg-primary px-4 py-1 text-white rounded-full"
                onclick="showSection('upcoming')">Upcoming</span>

            <span id="ongoing-tab" class="cursor-pointer px-4 py-1 rounded-full"
                onclick="showSection('ongoing')">Ongoing</span>

            <span id="registered-tab" class="cursor-pointer px-4 py-1 rounded-full"
                onclick="showSection('registered')">Registered</span>

            <span id="completed-tab" class="cursor-pointer px-4 py-1 rounded-full"
                onclick="showSection('completed')">Completed</span>
        </div>
    </div>

    <section class="p-2 mt-4">

        {{-- ================= UPCOMING EVENTS ================= --}}
        <div id="upcoming-section">
            <h4 class="font-semibold mb-4">Upcoming Events</h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse ($upcomingEvents as $schedule)
                    @php
                        $event = $schedule->event;

                        // Time selection
                        if ($schedule->is_reserve_date === 'Y') {
                            $start = $event->reserve_start_time;
                            $end = $event->reserve_end_time;
                        } else {
                            $start = $event->start_time;
                            $end = $event->end_time;
                        }

                        $registered = \App\Models\StudentEventRegistration::where('event_schedule_id', $schedule->id)
                            ->whereHas('get_event_schedule', function ($query) use ($schedule) {
                                $query->where('department_id', $schedule->department->id);
                            })
                            ->count();

                        $available = $schedule->seat_count - $registered;
                    @endphp

                    <div class="bg-white rounded-2xl shadow hover:shadow-lg transition">
                        <div class="relative">
                            <img src="{{ asset('storage/' . $event->banner_image) }}"
                                class="rounded-t-2xl w-full h-48 object-cover">

                            @if ($event->event_type === 'paid')
                                <span
                                    class="absolute top-3 right-3 bg-[#FFC31F] text-white px-3 py-1 rounded-full text-sm">
                                    Premium
                                </span>
                            @endif

                            <span class="absolute top-10 right-3 bg-primary text-white px-3 py-1 rounded-full text-sm">
                                {{ $available }} Seats Available
                            </span>

                            <span
                                class="absolute bottom-3 left-3 bg-black/40 text-white px-3 py-1 rounded-full text-xs">
                                {{ $event->title }}
                            </span>
                        </div>

                        <div class="p-3 text-xs space-y-1">
                            <div>📅 {{ \Carbon\Carbon::parse($schedule->event_date)->format('d M Y') }}</div>
                            <div>⏰ {{ \Carbon\Carbon::parse($start)->format('h:i A') }} -
                                {{ \Carbon\Carbon::parse($end)->format('h:i A') }}</div>
                            <div>🏢 {{ $schedule->department->name }}</div>
                            <div>📍 {{ $event->location }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500">No upcoming events found.</p>
                @endforelse
            </div>
        </div>

        {{-- ================= ONGOING EVENTS ================= --}}
        <div id="ongoing-section" class="hidden">
            <h4 class="font-semibold mb-4">Ongoing Events</h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse ($ongoingEvents as $schedule)
                    @php
                        $event = $schedule->event;

                        if ($schedule->is_reserve_date === 'Y') {
                            $start = $event->reserve_start_time;
                            $end = $event->reserve_end_time;
                        } else {
                            $start = $event->start_time;
                            $end = $event->end_time;
                        }
                    @endphp

                    <div class="bg-white rounded-2xl shadow hover:shadow-lg transition">
                        <div class="relative">
                            <img src="{{ asset('storage/' . $event->banner_image) }}"
                                class="rounded-t-2xl w-full h-48 object-cover">

                            <span class="absolute top-3 right-3 bg-green-600 text-white px-3 py-1 rounded-full text-sm">
                                Ongoing
                            </span>

                            <span
                                class="absolute bottom-3 left-3 bg-black/40 text-white px-3 py-1 rounded-full text-xs">
                                {{ $event->title }}
                            </span>
                        </div>

                        <div class="p-3 text-xs space-y-1">
                            <div>📅 {{ \Carbon\Carbon::parse($schedule->event_date)->format('d M Y') }}</div>
                            <div>⏰ {{ \Carbon\Carbon::parse($start)->format('h:i A') }} -
                                {{ \Carbon\Carbon::parse($end)->format('h:i A') }}</div>
                            <div>🏢 {{ $schedule->department->name }}</div>
                            <div>📍 {{ $event->location }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500">No ongoing events.</p>
                @endforelse
            </div>
        </div>

        {{-- ================= REGISTERED EVENTS ================= --}}
        <div id="registered-section" class="hidden">
            <h4 class="font-semibold mb-4">Registered Events</h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
               @forelse ($registeredEvents as $reg)
    @php
        $schedule = $reg->get_event_schedule;

        if (!$schedule || !$schedule->event) {
            continue;
        }

        $event = $schedule->event;

        if ($schedule->is_reserve_date === 'Y') {
            $start = $event->reserve_start_time;
            $end = $event->reserve_end_time;
        } else {
            $start = $event->start_time;
            $end = $event->end_time;
        }
    @endphp

    <div class="bg-white rounded-2xl shadow hover:shadow-lg transition">
        <img src="{{ asset('storage/' . $event->banner_image) }}"
             class="rounded-t-2xl w-full h-48 object-cover">

        <div class="p-3 text-xs space-y-1">
            <div class="font-semibold">{{ $event->title }}</div>
            <div>📅 {{ \Carbon\Carbon::parse($schedule->event_date)->format('d M Y') }}</div>
            <div>⏰ {{ \Carbon\Carbon::parse($start)->format('h:i A') }} -
                {{ \Carbon\Carbon::parse($end)->format('h:i A') }}</div>
            <div>🏢 {{ $schedule->department->name ?? '-' }}</div>
            <div>📍 {{ $event->location }}</div>
        </div>
    </div>

@empty
    <p class="text-gray-500">No registered events.</p>
@endforelse

            </div>
        </div>

        {{-- ================= COMPLETED EVENTS ================= --}}
        <div id="completed-section" class="hidden">
            <h4 class="font-semibold mb-4">Completed Events</h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse ($completedEvents as $schedule)
                    @php
                        $event = $schedule->event;
                    @endphp

                    <div class="bg-white rounded-2xl shadow opacity-75">
                        <img src="{{ asset('storage/' . $event->banner_image) }}"
                            class="rounded-t-2xl w-full h-48 object-cover">

                        <div class="p-3 text-xs space-y-1">
                            <div class="font-semibold">{{ $event->title }}</div>
                            <div>📅 {{ \Carbon\Carbon::parse($schedule->event_date)->format('d M Y') }}</div>
                            <div>🏢 {{ $schedule->department->name }}</div>
                            <div>📍 {{ $event->location }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500">No completed events.</p>
                @endforelse
            </div>
        </div>

    </section>

    {{-- ================= TAB SCRIPT ================= --}}
    <script>
        function showSection(type) {
            ['upcoming', 'ongoing', 'registered', 'completed'].forEach(t => {
                document.getElementById(t + '-section').classList.add('hidden');
                document.getElementById(t + '-tab').classList.remove('bg-primary', 'text-white');
            });

            document.getElementById(type + '-section').classList.remove('hidden');
            document.getElementById(type + '-tab').classList.add('bg-primary', 'text-white');
        }
    </script>

</x-layouts.app>
