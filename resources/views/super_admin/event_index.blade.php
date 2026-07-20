<x-layouts.app>
    {{-- ================= HEADER ================= --}}
    <div class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-8 py-3">
        <h3 class="font-semibold text-primary">Events</h3>
    </div>
    {{-- ================= DATE FILTER ================= --}}
    <form method="GET" action="{{ route('events') }}" class="bg-white p-4 rounded-xl shadow mt-4 mx-5">
        <input type="hidden" name="tab" id="tab-input" value="{{ request('tab') }}">
        <div class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="text-sm font-medium">From Date</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}"
                    class="border rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="text-sm font-medium">To Date</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}"
                    class="border rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Programme</label>
                <select name="programme_id" id="programme_id" class="choice-select border rounded-lg px-3 py-2 w-full">
                    <option value="">-- Select Programme --</option>
                    @foreach ($programmes as $programme)
                        <option value="{{ $programme->id }}"
                            {{ request('programme_id') == $programme->id ? 'selected' : '' }}>
                            {{ $programme->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <input type="hidden" name="tab" value="{{ request('tab') }}">
            <div>
                <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg">
                    Filter
                </button>
            </div>
            <div>
                <a href="{{ route('events') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg">
                    Reset
                </a>
            </div>
        </div>
    </form>
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

    <section class="p-5 mt-2">
        <div id="upcoming-section">
            <h4 class="font-semibold mb-4">Upcoming Events</h4>
            <div id="upcoming-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse ($upcomingEvents as $schedule)
                    @php
                        $event = $schedule->event;
                    @endphp
                    <div class="bg-white rounded-2xl shadow hover:shadow-lg transition">
                        <div class="relative">
                            <img src="{{ asset('storage/' . $event->banner_image) }}"
                                class="rounded-t-2xl w-full h-48 object-cover">
                            <span
                                class="absolute bottom-3 left-3 bg-[rgba(128,128,128,0.4)] text-white text-xs px-3 py-1 rounded-full">
                                {{ $schedule->programme->name ?? '' }}
                            </span>
                        </div>
                        <div class="p-3 text-sm">
                            <div class="font-semibold">{{ $event->title }}</div>
                            <div>👤 {{ $event->get_faculty->name ?? '-' }}</div>
                            <div>📅 {{ \Carbon\Carbon::parse($schedule->event_date)->format('d M Y') }}</div>
                            <div>📍 {{ $event->location }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500">No upcoming events</p>
                @endforelse
            </div>
        </div>

        {{-- ================= ONGOING ================= --}}
        <div id="ongoing-section" class="hidden">
            <h4 class="font-semibold mb-4">Ongoing Events</h4>
            <div id="ongoing-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse ($ongoingEvents as $schedule)
                    @php $event = $schedule->event; @endphp
                    <div class="bg-white rounded-2xl shadow">
                        <div class="relative">
                            <img src="{{ asset('storage/' . $event->banner_image) }}"
                                class="rounded-t-2xl w-full h-48 object-cover">
                            <span
                                class="absolute bottom-3 left-3 bg-[rgba(128,128,128,0.4)] text-white text-xs px-3 py-1 rounded-full">
                                {{ $schedule->programme->name ?? '' }}
                            </span>
                        </div>
                        <div class="p-3 text-sm">
                            <div class="font-semibold">{{ $event->title }}</div>
                            <div>👤 {{ $event->get_faculty->name ?? '-' }}</div>
                            <div>📅 {{ \Carbon\Carbon::parse($schedule->event_date)->format('d M Y') }}</div>
                            <div>📍 {{ $event->location }}</div>
                        </div>
                    </div>
                @empty
                    <p>No ongoing events</p>
                @endforelse
            </div>
        </div>

        <div id="registered-section" class="hidden">
            <h4 class="font-semibold mb-4">Registered Events</h4>
            <div id="registered-container">
                <table class="min-w-full bg-white rounded-xl shadow overflow-hidden">
                    <thead class="bg-primary text-white text-sm">
                        <tr>
                            <th class="px-4 py-2 text-left">Event</th>
                            <th class="px-4 py-2 text-left">Programme</th>
                            <th class="px-4 py-2 text-left">Date</th>
                            <th class="px-4 py-2 text-left">Total Students</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        @forelse($registeredEvents as $schedule)
                            <tr class="border-b">
                                <td class="px-4 py-2">
                                    {{ $schedule->event->title ?? '-' }}
                                </td>
                                <td class="px-4 py-2">
                                    {{ $schedule->programme->name ?? '-' }} - {{ $schedule->section ?? '' }}
                                </td>
                                <td class="px-4 py-2">
                                    {{ \Carbon\Carbon::parse($schedule->event_date)->format('d M Y') }}
                                </td>
                                <td class="px-4 py-2 font-semibold">
                                    {{ $schedule->total_students }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4">
                                    No registered events
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-6">
                {{ $registeredEvents->appends(['tab' => 'registered'])->links() }}
            </div>
        </div>

        {{-- ================= COMPLETED ================= --}}
        <div id="completed-section" class="hidden">
            <h4 class="font-semibold mb-4">Completed Events (Department Wise)</h4>
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th class="px-4 py-3 text-left">Event</th>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-left">Departments Attended</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($completedEvents as $schedule)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium">
                                    {{ $schedule->event->title }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ \Carbon\Carbon::parse($schedule->event_date)->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($schedule->departments->count())
                                        @php
                                            $colors = [
                                                ['bg-blue-100', 'text-blue-700'],
                                                ['bg-green-100', 'text-green-700'],
                                                ['bg-red-100', 'text-red-700'],
                                                ['bg-yellow-100', 'text-yellow-700'],
                                                ['bg-purple-100', 'text-purple-700'],
                                                ['bg-pink-100', 'text-pink-700'],
                                                ['bg-indigo-100', 'text-indigo-700'],
                                                ['bg-teal-100', 'text-teal-700'],
                                            ];
                                        @endphp

                                        @foreach ($schedule->departments as $index => $dept)
                                            @php
                                                $color = $colors[$index % count($colors)];
                                            @endphp

                                            <span
                                                class="{{ $color[0] }} {{ $color[1] }} px-2 py-1 rounded-full text-xs mr-1">
                                                {{ $dept }} - {{ $schedule->section ?? '' }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="text-gray-400 text-xs">No Departments</span>
                                    @endif
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-6 text-gray-500">
                                    No completed events
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-6">
                {{ $completedEvents->appends(['tab' => 'completed'])->links() }}
            </div>
        </div>
    </section>
</x-layouts.app>

{{-- ================= TAB SCRIPT ================= --}}
<script>
    document.addEventListener("DOMContentLoaded", function() {
        let params = new URLSearchParams(window.location.search);
        let activeTab = params.get('tab') || 'upcoming';;
        if (activeTab) {
            showSection(activeTab);
        }
    });

    let currentTab = new URLSearchParams(window.location.search).get('tab') || 'upcoming';

    function showSection(type, updateUrl = true, isInitial = false) {
        ['upcoming', 'ongoing', 'registered', 'completed'].forEach(tab => {
            document.getElementById(tab + '-section').classList.add('hidden');
            document.getElementById(tab + '-tab')
                .classList.remove('bg-primary', 'text-white');
        });

        document.getElementById(type + '-section')
            .classList.remove('hidden');

        document.getElementById(type + '-tab')
            .classList.add('bg-primary', 'text-white');

        currentTab = type;
        document.getElementById('tab-input').value = type;

        if (updateUrl) {
            let params = new URLSearchParams(window.location.search);
            params.set('tab', type);
            let newUrl = window.location.pathname + '?' + params.toString();

            if (isInitial) {
                history.replaceState({
                    tab: type
                }, '', newUrl);
            } else {
                history.pushState({
                    tab: type
                }, '', newUrl);
            }
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        let params = new URLSearchParams(window.location.search);
        let activeTab = params.get('tab') || 'upcoming';
        showSection(activeTab, true, true);
    });

    window.addEventListener('popstate', function() {
        let params = new URLSearchParams(window.location.search);
        let tab = params.get('tab') || 'upcoming';
        showSection(tab, false);
    });

    document.getElementById('from_date').addEventListener('change', fetchEvents);
    document.getElementById('to_date').addEventListener('change', fetchEvents);

    function fetchEvents() {
        let from = document.getElementById('from_date').value;
        let to = document.getElementById('to_date').value;

        if (!from && !to) return;

        let url = `{{ route('events') }}?`;
        if (from) url += `from_date=${from}&`;
        if (to) url += `to_date=${to}`;

        fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                renderCards('upcoming-container', data.upcoming);
                renderCards('ongoing-container', data.ongoing);
                renderCompleted('completed-container', data.completed);
                renderRegistered(data.registered);
            });
    }

    function renderCards(containerId, events) {
        let container = document.getElementById(containerId);
        container.innerHTML = '';

        if (!events || events.length === 0) {
            container.innerHTML = '<p class="text-gray-500">No events found</p>';
            return;
        }

        events.forEach(schedule => {
            let event = schedule.event;

            container.innerHTML += `
        <div class="bg-white rounded-2xl shadow hover:shadow-lg transition">
            <img src="/storage/${event.banner_image}"
                 class="rounded-t-2xl w-full h-48 object-cover">
            <div class="p-3 text-sm">
                <div class="font-semibold">${event.title}</div>
                <div>📅 ${schedule.event_date}</div>
                <div>📍 ${event.location}</div>
            </div>
        </div>`;
        });
    }

    function renderCompleted(containerId, events) {
        document.getElementById('completed-count').innerText = events.length;
        renderCards(containerId, events);
    }

    function renderRegistered(groupedData) {
        let container = document.getElementById('registered-container');
        container.innerHTML = '';

        let table = `
    <table class="min-w-full bg-white rounded-xl shadow overflow-hidden">
        <thead class="bg-primary text-white text-sm">
            <tr>
                <th class="px-4 py-2 text-left">Event</th>
                <th class="px-4 py-2 text-left">Date</th>
                <th class="px-4 py-2 text-left">Total Students</th>
            </tr>
        </thead>
        <tbody class="text-sm">`;

        for (let key in groupedData) {
            let registrations = groupedData[key];
            let schedule = registrations[0].get_event_schedule;
            let event = schedule.event;

            table += `
        <tr class="border-b">
            <td class="px-4 py-2">${event.title}</td>
            <td class="px-4 py-2">${schedule.event_date}</td>
            <td class="px-4 py-2 font-semibold">${registrations.length}</td>
        </tr>`;
        }
        table += `</tbody></table>`;
        container.innerHTML = table;
    }
</script>
<script src="{{ asset('admin/js/common.js') }}?v={{ time() }}"></script>
