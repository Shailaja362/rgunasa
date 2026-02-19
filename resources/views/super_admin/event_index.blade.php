<x-layouts.app>

{{-- ================= HEADER ================= --}}
<div class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-8 py-3">
    <h3 class="font-semibold text-primary">Events</h3>
</div>


{{-- ================= DATE FILTER ================= --}}
<div class="bg-white p-4 rounded-xl shadow mt-4 mx-5">
    <div class="flex flex-wrap gap-4">

        <div>
            <label class="text-sm font-medium">From Date</label>
            <input type="date" id="from_date"
                class="border rounded-lg px-3 py-2">
        </div>

        <div>
            <label class="text-sm font-medium">To Date</label>
            <input type="date" id="to_date"
                class="border rounded-lg px-3 py-2">
        </div>

    </div>
</div>


{{-- ================= TABS ================= --}}
<div class="px-5 py-3 mt-4">
    <div class="flex space-x-4 text-gray-700 font-medium">

        <span id="upcoming-tab"
            class="cursor-pointer bg-primary px-4 py-1 text-white rounded-full"
            onclick="showSection('upcoming')">
            Upcoming
        </span>

        <span id="ongoing-tab"
            class="cursor-pointer px-4 py-1 rounded-full"
            onclick="showSection('ongoing')">
            Ongoing
        </span>

        <span id="registered-tab"
            class="cursor-pointer px-4 py-1 rounded-full"
            onclick="showSection('registered')">
            Registered
        </span>

        <span id="completed-tab"
            class="cursor-pointer px-4 py-1 rounded-full"
            onclick="showSection('completed')">
            Completed
        </span>

    </div>
</div>


<section class="p-5 mt-2">

{{-- ================= UPCOMING ================= --}}
<div id="upcoming-section">

    <h4 class="font-semibold mb-4">Upcoming Events</h4>

    <div id="upcoming-container"
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

        @forelse ($upcomingEvents as $schedule)

            @php $event = $schedule->event; @endphp

            <div class="bg-white rounded-2xl shadow hover:shadow-lg transition">

                <img src="{{ asset('storage/'.$event->banner_image) }}"
                    class="rounded-t-2xl w-full h-48 object-cover">

                <div class="p-3 text-sm">

                    <div class="font-semibold">
                        {{ $event->title }}
                    </div>

                    <div>
                        📅 {{ \Carbon\Carbon::parse($schedule->event_date)->format('d M Y') }}
                    </div>

                    <div>
                        📍 {{ $event->location }}
                    </div>

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

    <div id="ongoing-container"
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

        @forelse ($ongoingEvents as $schedule)

            @php $event = $schedule->event; @endphp

            <div class="bg-white rounded-2xl shadow">

                <img src="{{ asset('storage/'.$event->banner_image) }}"
                    class="rounded-t-2xl w-full h-48 object-cover">

                <div class="p-3 text-sm">

                    <div class="font-semibold">
                        {{ $event->title }}
                    </div>

                    <div>
                        📅 {{ \Carbon\Carbon::parse($schedule->event_date)->format('d M Y') }}
                    </div>

                    <div>
                        📍 {{ $event->location }}
                    </div>

                </div>

            </div>

        @empty

            <p>No ongoing events</p>

        @endforelse

    </div>

</div>


{{-- ================= REGISTERED ================= --}}
<div id="registered-section" class="hidden">

    <h4 class="font-semibold mb-4">Registered Events</h4>

    <div id="registered-container"
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

        @forelse ($registeredEvents as $reg)

            @php
                $schedule = $reg->get_event_schedule;
                if(!$schedule) continue;
                $event = $schedule->event;
            @endphp

            <div class="bg-white rounded-2xl shadow">

                <img src="{{ asset('storage/'.$event->banner_image) }}"
                    class="rounded-t-2xl w-full h-48 object-cover">

                <div class="p-3 text-sm">

                    <div class="font-semibold">
                        {{ $event->title }}
                    </div>

                    <div>
                        📅 {{ \Carbon\Carbon::parse($schedule->event_date)->format('d M Y') }}
                    </div>

                    <div>
                        📍 {{ $event->location }}
                    </div>

                </div>

            </div>

        @empty

            <p>No registered events</p>

        @endforelse

    </div>

</div>


{{-- ================= COMPLETED ================= --}}
<div id="completed-section" class="hidden">

    <h4 class="font-semibold mb-4">Completed Events</h4>

    <div id="completed-container"
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

        @forelse ($completedEvents as $schedule)

            @php $event = $schedule->event; @endphp

            <div class="bg-white rounded-2xl shadow opacity-75">

                <img src="{{ asset('storage/'.$event->banner_image) }}"
                    class="rounded-t-2xl w-full h-48 object-cover">

                <div class="p-3 text-sm">

                    <div class="font-semibold">
                        {{ $event->title }}
                    </div>

                    <div>
                        📅 {{ \Carbon\Carbon::parse($schedule->event_date)->format('d M Y') }}
                    </div>

                    <div>
                        📍 {{ $event->location }}
                    </div>

                </div>

            </div>

        @empty

            <p>No completed events</p>

        @endforelse

    </div>

</div>

</section>


{{-- ================= TAB SCRIPT ================= --}}
<script>

function showSection(type)
{
    ['upcoming','ongoing','registered','completed'].forEach(tab =>
    {
        document.getElementById(tab+'-section').classList.add('hidden');

        document.getElementById(tab+'-tab')
            .classList.remove('bg-primary','text-white');
    });

    document.getElementById(type+'-section')
        .classList.remove('hidden');

    document.getElementById(type+'-tab')
        .classList.add('bg-primary','text-white');
}

</script>


{{-- ================= AJAX FILTER SCRIPT ================= --}}
<script>

document.getElementById('from_date').addEventListener('change', fetchEvents);
document.getElementById('to_date').addEventListener('change', fetchEvents);

function fetchEvents()
{
    let from = document.getElementById('from_date').value;
    let to   = document.getElementById('to_date').value;

    if(!from || !to) return;

    fetch(`{{ route('superadmin.events.index') }}?from_date=${from}&to_date=${to}`, {
        headers:
        {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data =>
    {
        renderEvents('upcoming-container', data.upcoming);
        renderEvents('ongoing-container', data.ongoing);
        renderEvents('completed-container', data.completed);
    });
}


function renderEvents(containerId, events)
{
    let container = document.getElementById(containerId);

    container.innerHTML = '';

    if(events.length === 0)
    {
        container.innerHTML = '<p>No events found</p>';
        return;
    }

    events.forEach(schedule =>
    {
        let event = schedule.event;

        container.innerHTML += `
            <div class="bg-white rounded-2xl shadow">

                <img src="/storage/${event.banner_image}"
                     class="rounded-t-2xl w-full h-48 object-cover">

                <div class="p-3 text-sm">

                    <div class="font-semibold">
                        ${event.title}
                    </div>

                    <div>
                        📅 ${schedule.event_date}
                    </div>

                    <div>
                        📍 ${event.location}
                    </div>

                </div>

            </div>
        `;
    });
}
</script>

</x-layouts.app>
