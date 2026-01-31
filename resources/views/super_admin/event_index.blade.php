<x-layouts.app>
    <div class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-8 py-3">
        <h3 class="font-semibold text-primary">Events</h3>
    </div>
    <div class="rounded-full px-5 py-3 mt-4 flex justify-between items-center">
        <div class="flex space-x-4 text-gray-700 font-medium">
            <span id="upcoming-tab" class="cursor-pointer bg-primary px-3 text-white rounded-full transition"
                onclick="showSection('upcoming')">Upcoming</span>
            <span id="ongoing-tab" class="cursor-pointer px-3 rounded-full transition"
                onclick="showSection('ongoing')">Ongoing</span>
            <span id="registered-tab" class="cursor-pointer px-3 rounded-full transition"
                onclick="showSection('registered')">Registered</span>
            <span id="completed-tab" class="cursor-pointer px-3 rounded-full transition"
                onclick="showSection('completed')">Completed</span>
        </div>
    </div>
    <!-- Dashboard Header -->
    <section class="p-2 mt-3">

        <!-- Upcoming Events -->
        <div id="upcoming-section" class="mt-6">
            <h4 class="font-semibold text-gray-800 mb-4">Upcoming Events</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($upcomingEvents as $event)
                    @php
                        $schedule = $event->schedules->first(); // pick first schedule
                        $registered = $schedule
                            ? \App\Models\StudentEventRegistration::where('event_schedule_id', $schedule->id)->count()
                            : 0;
                        $available = $schedule ? $schedule->seat_count - $registered : 0;
                    @endphp

                    <div class="bg-white rounded-2xl shadow hover:shadow-lg transition">
                        <div class="relative">
                            <img src="{{ asset('storage/' . $event->banner_image) }}" alt="Event"
                                class="rounded-t-2xl w-full h-48 object-cover">

                            @if ($event->event_type == 'paid')
                                <span class="absolute top-3 right-3 bg-[#FFC31F] text-white px-3 text-sm py-1 rounded-full">
                                    Premium
                                </span>
                            @endif
                            <span
                                class="absolute @if ($event->event_type == 'paid') mt-2 top-10 @else top-3 @endif right-3 bg-gradient-to-r from-primary to-pink-600 text-white px-3 text-sm py-1 rounded-full">
                                <span class="text-2xl">{{ $available }}</span> Seats Available
                            </span>
                            <span
                                class="absolute bottom-3 left-3 bg-[rgba(128,128,128,0.4)] text-white text-xs px-3 py-1 rounded-full">
                                {{ $event->title }}
                            </span>
                        </div>
                        <div class="p-2 details">
                            <div class="grid grid-cols-1 gap-1 md:grid-cols-4 text-xs">
                                <div class="col-span-2 flex items-center bg-[#F2E8F5] rounded-full px-1 py-1">
                                    <i class="fa fa-clock text-primary"></i>
                                    <p class="px-1">
                                        {{ $schedule ? \Carbon\Carbon::parse($schedule->start_time)->format('h:iA') : '-' }}
                                        -
                                        {{ $schedule ? \Carbon\Carbon::parse($schedule->end_time)->format('h:iA') : '-' }}
                                    </p>
                                </div>
                                <div class="col-span-2 flex items-center bg-[#F2E8F5] rounded-full px-2 py-1">
                                    <i class="fa fa-calendar text-primary"></i>
                                    <p class="px-1">
                                        {{ $schedule ? \Carbon\Carbon::parse($schedule->event_date)->format('F j, Y') : '-' }}
                                    </p>
                                </div>
                                <div class="col-span-4 flex items-center bg-[#F2E8F5] rounded-full px-1 py-1 mt-2">
                                    <i class="fa fa-map-marker text-primary"></i>
                                    <p class="px-1">{{ $event ? $event->location : '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Ongoing Events -->
        <div id="ongoing-section" class="mt-6 hidden">
            <h4 class="font-semibold text-gray-800 mb-4">Ongoing Events</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($ongoingEvents as $event)
                    @php
                        $schedule = $event->schedules->first();
                        $registered = $schedule
                            ? \App\Models\StudentEventRegistration::where('event_schedule_id', $schedule->id)->count()
                            : 0;
                        $available = $schedule ? $schedule->seat_count - $registered : 0;
                    @endphp

                    <div class="bg-white rounded-2xl shadow hover:shadow-lg transition">
                        <div class="relative">
                            <img src="{{ asset('storage/' . $event->banner_image) }}" alt="Event"
                                class="rounded-t-2xl w-full h-48 object-cover">

                            @if ($event->event_type == 'paid')
                                <span
                                    class="absolute top-3 right-3 bg-[#FFC31F] text-white px-3 text-sm py-1 rounded-full">
                                    Premium
                                </span>
                            @endif

                            <span
                                class="absolute @if ($event->event_type == 'paid') mt-2 top-10 @else top-3 @endif right-3 bg-gradient-to-r from-primary to-pink-600 text-white px-3 text-sm py-1 rounded-full">
                                <span class="text-2xl">{{ $available }}</span> Seats Available
                            </span>

                            <span
                                class="absolute bottom-3 left-3 bg-[rgba(128,128,128,0.4)] text-white text-xs px-3 py-1 rounded-full">
                                {{ $event->title }}
                            </span>
                        </div>

                        <div class="p-2 details">
                            <div class="grid grid-cols-1 gap-1 md:grid-cols-4 text-xs">
                                <div class="col-span-2 flex items-center bg-[#F2E8F5] rounded-full px-1 py-1">
                                    <i class="fa fa-clock text-primary"></i>
                                    <p class="px-1">
                                        {{ $schedule ? \Carbon\Carbon::parse($schedule->start_time)->format('h:iA') : '-' }}
                                        -
                                        {{ $schedule ? \Carbon\Carbon::parse($schedule->end_time)->format('h:iA') : '-' }}
                                    </p>
                                </div>

                                <div class="col-span-2 flex items-center bg-[#F2E8F5] rounded-full px-2 py-1">
                                    <i class="fa fa-calendar text-primary"></i>
                                    <p class="px-1">
                                        {{ $schedule ? \Carbon\Carbon::parse($schedule->event_date)->format('F j, Y') : '-' }}
                                    </p>
                                </div>

                                <div class="col-span-4 flex items-center bg-[#F2E8F5] rounded-full px-1 py-1 mt-2">
                                    <i class="fa fa-map-marker text-primary"></i>
                                    <p class="px-1">{{ $event ? $event->location : '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Registered Events -->
        <div id="registered-section" class="mt-6 hidden">
            <h4 class="font-semibold text-gray-800 mb-4">Registered Events</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($registeredEvents as $registration)
                    @php
                        $event = $registration->event;
                        $schedule = $event->schedules->first();
                        $registered = $schedule
                            ? \App\Models\StudentEventRegistration::where('event_schedule_id', $schedule->id)->count()
                            : 0;
                        $available = $schedule ? $schedule->seat_count - $registered : 0;
                    @endphp

                    <div class="bg-white rounded-2xl shadow hover:shadow-lg transition">
                        <div class="relative">
                            <img src="{{ asset('storage/' . $event->banner_image) }}" alt="Event"
                                class="rounded-t-2xl w-full h-48 object-cover">

                            @if ($event->event_type == 'paid')
                                <span
                                    class="absolute top-3 right-3 bg-[#FFC31F] text-white px-3 text-sm py-1 rounded-full">
                                    Premium
                                </span>
                            @endif

                            <span
                                class="absolute @if ($event->event_type == 'paid') mt-2 top-10 @else top-3 @endif right-3 bg-gradient-to-r from-primary to-pink-600 text-white px-3 text-sm py-1 rounded-full">
                                <span class="text-2xl">{{ $available }}</span> Seats Available
                            </span>

                            <span
                                class="absolute bottom-3 left-3 bg-[rgba(128,128,128,0.4)] text-white text-xs px-3 py-1 rounded-full">
                                {{ $event->title }}
                            </span>
                        </div>

                        <div class="p-2 details">
                            <div class="grid grid-cols-1 gap-1 md:grid-cols-4 text-xs">
                                <div class="col-span-2 flex items-center bg-[#F2E8F5] rounded-full px-1 py-1">
                                    <i class="fa fa-clock text-primary"></i>
                                    <p class="px-1">
                                        {{ $schedule ? \Carbon\Carbon::parse($schedule->start_time)->format('h:iA') : '-' }}
                                        -
                                        {{ $schedule ? \Carbon\Carbon::parse($schedule->end_time)->format('h:iA') : '-' }}
                                    </p>
                                </div>

                                <div class="col-span-2 flex items-center bg-[#F2E8F5] rounded-full px-2 py-1">
                                    <i class="fa fa-calendar text-primary"></i>
                                    <p class="px-1">
                                        {{ $schedule ? \Carbon\Carbon::parse($schedule->event_date)->format('F j, Y') : '-' }}
                                    </p>
                                </div>

                                <div class="col-span-4 flex items-center bg-[#F2E8F5] rounded-full px-1 py-1 mt-2">
                                    <i class="fa fa-map-marker text-primary"></i>
                                    <p class="px-1">{{ $event ? $event->location : '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Completed Events -->
        <div id="completed-section" class="mt-6 hidden">
            <h4 class="font-semibold text-gray-800 mb-4">Completed Events</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($completedEvents as $registration)
                    @php
                        $event = $registration->event;
                        $schedule = $event->schedules->first();
                        $registered = $schedule
                            ? \App\Models\StudentEventRegistration::where('event_schedule_id', $schedule->id)->count()
                            : 0;
                        $available = $schedule ? $schedule->seat_count - $registered : 0;
                    @endphp

                    <div class="bg-white rounded-2xl shadow hover:shadow-lg transition">
                        <div class="relative">
                            <img src="{{ asset('storage/' . $event->banner_image) }}" alt="Event"
                                class="rounded-t-2xl w-full h-48 object-cover">

                            @if ($event->event_type == 'paid')
                                <span
                                    class="absolute top-3 right-3 bg-[#FFC31F] text-white px-3 text-sm py-1 rounded-full">
                                    Premium
                                </span>
                            @endif

                            <span
                                class="absolute bottom-3 left-3 bg-[rgba(128,128,128,0.4)] text-white text-xs px-3 py-1 rounded-full">
                                {{ $event->title }}
                            </span>
                        </div>

                        <div class="p-2 details">
                            <div class="grid grid-cols-1 gap-1 md:grid-cols-4 text-xs">
                                <div class="col-span-2 flex items-center bg-[#F2E8F5] rounded-full px-1 py-1">
                                    <i class="fa fa-clock text-primary"></i>
                                    <p class="px-1">
                                        {{ $schedule ? \Carbon\Carbon::parse($schedule->start_time)->format('h:iA') : '-' }}
                                        -
                                        {{ $schedule ? \Carbon\Carbon::parse($schedule->end_time)->format('h:iA') : '-' }}
                                    </p>
                                </div>

                                <div class="col-span-2 flex items-center bg-[#F2E8F5] rounded-full px-2 py-1">
                                    <i class="fa fa-calendar text-primary"></i>
                                    <p class="px-1">
                                        {{ $schedule ? \Carbon\Carbon::parse($schedule->event_date)->format('F j, Y') : '-' }}
                                    </p>
                                </div>

                                <div class="col-span-4 flex items-center bg-[#F2E8F5] rounded-full px-1 py-1 mt-2">
                                    <i class="fa fa-map-marker text-primary"></i>
                                    <p class="px-1">{{ $event ? $event->location : '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

</x-layouts.app>

<script src="{{ asset('admin/js/events.js') }}"></script>
