<x-layouts.app>
    <div class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-8 py-3">
        <h3 class="font-semibold text-primary">Events</h3>
    </div>
    <div class="flex justify-end">
        <a href="{{ route('create_event') }}"
            class="px-2 w-40 mt-3 bg-gradient-to-r from-primary to-pink-600 text-white font-medium py-1 rounded-full"><i
                class="fa fa-plus" aria-hidden="true"></i>Create Event</a>
    </div>
    <!-- Dashboard Header -->
    @if (!empty(session()->get('admin')))
        <div class="taskdiv grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6 mt-5">
            @foreach ($tasks as $task)
                @if (empty($task->get_event))
                    <div class="task-card bg-white rounded-2xl shadow hover:shadow-lg transition p-5 flex flex-col justify-between"
                        data-admin="{{ $task->get_admin->id ?? '' }}" data-status="{{ $task->priority ?? '' }}"
                        data-event="{{ $task->title ?? '' }}">
                        <!-- Header -->
                        <div class="flex items-start justify-between mb-2">
                            <p class="font-semibold text-lg text-primary truncate w-full"
                                title="{{ $task->title ?? '' }}">
                                {{ $task->title ?? '' }}
                            </p>
                            <span class="text-sm bg-primary text-white px-3 py-1 rounded-full">
                                {{ ucfirst($task->priority ?? '') }}
                            </span>
                        </div>
                        <!-- Admin -->
                        <p class="text-sm text-gray-500 mb-2">By: {{ $task->get_creator->name ?? '' }}</p>
                        <!-- Description -->
                        <p class="text-sm truncate w-full mb-3">{{ $task->description ?? '' }}</p>
                        <!-- Status & Deadline -->
                        <div class="flex flex-wrap gap-2 mb-3 text-xs">
                            @if ($task->status == 'completed')
                                <div
                                    class="flex items-center bg-gradient-to-r from-primary to-pink-600 rounded-full px-4 py-1 text-white">
                                    Accept
                                </div>
                            @else
                                <div class="flex items-center bg-[#F2E8F5] rounded-full px-3 py-1">
                                    <span class="ml-1">
                                        @if ($task->status == 'pending')
                                            <i class="fa fa-hourglass-half text-primary"></i>Pending
                                        @elseif($task->status == 'accepted')
                                            <i class="fa fa-check-circle text-primary"></i> Accepted
                                        @else
                                            In progress
                                        @endif
                                    </span>
                                </div>
                            @endif
                            <div class="flex items-center bg-[#F2E8F5] rounded-full px-3 py-1">
                                <i class="fa fa-calendar text-primary"></i>
                                <span class="ml-1">
                                    {{ !empty($task->deadline_date) ? \Carbon\Carbon::parse($task->deadline_date)->format('M d, h:i A') : '' }}
                                </span>
                            </div>
                        </div>
                        <!-- Attachments & Edit -->
                        <div class="flex items-center justify-between text-xs mt-auto">
                            <p class="text-gray-600">
                                <span class="text-primary">•</span>
                                <span
                                    class="ml-1">{{ isset($task->get_task_images) ? count($task->get_task_images) : 0 }}
                                    Attachments</span>
                            </p>
                            @php
                                $current_date = now();
                            @endphp

                            @if (\Carbon\Carbon::parse($task->deadline_date)->gte($current_date))
                                <a href="{{ route('create_event', ['task_id' => encrypt($task->id)]) }}"
                                    class="px-2 w-30 mt-3 bg-gradient-to-r from-primary to-pink-600 text-white font-medium py-1 rounded-full">
                                    <i class="fa fa-plus" aria-hidden="true"></i> Create Event
                                </a>
                            @endif
                            <a href="{{ route('task_view', ['task_id' => encrypt($task->id)]) }}"
                                class="px-2 w-30 mt-3 bg-gray-200 text-gray-800 font-medium py-1 rounded-full flex items-center justify-center hover:bg-gray-300">
                                <i class="fa fa-eye mr-1" aria-hidden="true"></i> View
                            </a>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
    <section class="p-2 mt-3">
        <div class="mt-6">
            <h4 class="font-semibold text-gray-800 mb-4">Event Lists</h4>
            <div class="overflow-x-auto bg-white rounded-xl shadow-md">
                <table class="w-full text-sm text-left text-gray-700 border-collapse">
                    <thead>
                        <tr class="bg-primary text-white text-sm uppercase tracking-wider">
                            <th class="px-2 py-2">#</th>
                            <th class="px-2 py-2">Banner</th>
                            <th class="px-2 py-2">Event Name</th>
                            <th class="px-2 py-2">Programme Officer</th>
                            <th class="px-2 py-2">Departments</th>
                            <th class="px-2 py-2">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        @foreach ($events as $event)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-3 py-2 font-medium">{{ $loop->iteration }}</td>
                                <td class="px-3 py-2">
                                    @if ($event->banner_image)
                                        <img src="{{ asset('storage/' . $event->banner_image) }}"
                                            class="h-10 w-10 object-cover rounded-lg border" />
                                    @else
                                        <span class="text-gray-400 italic">No Image</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">{{ $event->title }}</td>
                                <td class="px-3 py-2">{{ $event->get_faculty->name ?? '-' }}</td>
                                <td class="px-3 py-2">
                                    <div class="grid grid-cols-2 md:grid-cols-2 gap-2">
                                        @foreach ($event->schedules as $schedule)
                                            <div
                                                class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 hover:shadow-md transition">
                                                <div class="text-xs font-bold text-gray-800 mb-1">
                                                    {{ $schedule->programme->name }}
                                                </div>
                                                <div class="text-xs text-gray-600 mb-1">
                                                    <span class="font-medium">Event Date:</span>
                                                    {{ \Carbon\Carbon::parse($schedule->event_date)->format('d-m-Y') }}
                                                </div>
                                                <div class="text-xs text-gray-600 mb-1">
                                                    <span class="font-medium">Reserve Date:</span>
                                                    <span
                                                        class="{{ $schedule->is_reserve_date == 'y' ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold' }}">
                                                        {{ $schedule->is_reserve_date == 'y' ? 'Yes' : 'No' }}
                                                    </span>
                                                </div>
                                                <div class="text-xs text-gray-600">
                                                    <span class="font-medium">Section:</span>
                                                    {{ strtoupper($schedule->section) }}
                                                    <span class="mx-2">|</span>
                                                    <span class="font-medium">Seats:</span>
                                                    {{ $schedule->seat_count }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <!-- Action -->
                                <td class="px-3 py-2 text-center">
                                    <a href="{{ route('create_event', ['event_id' => encrypt($event->id)]) }}"
                                        class="text-blue-600 hover:text-blue-800">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    @php
                                        $canDelete = $event->schedules->every(function ($schedule) {
                                            return $schedule->registrations->isEmpty();
                                        });
                                    @endphp

                                    @if ($canDelete)
                                        <button type="button" class="text-red-600 hover:text-red-800 deleteEvent" id="deleteEvent" data-id="{{ $event->id }}">
                                            <i class="fa-solid fa-delete-left"></i>
                                        </button>
                                    @endif

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">
                {{ $events->links() }}
            </div>
        </div>
    </section>

 <div id="deleteModal"
     class="fixed inset-0 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-96 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">
            Confirm Delete
        </h2>
        <p class="text-gray-600 mb-5">
            Are you sure you want to delete this event?
        </p>
        <div class="flex justify-end gap-3">
            <button id="cancelDelete"
                class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                Cancel
            </button>
            <button id="confirmDelete"
                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                Delete
            </button>
        </div>
    </div>
 </div>

</x-layouts.app>

<script src="{{ asset('admin/js/events.js') }}?v={{ time() }}"></script>
