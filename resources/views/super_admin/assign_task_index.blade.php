<x-layouts.app>
    <div class="bg-[#F5E8F5] w-full  rounded-full shadow-sm px-8 py-3">
        <h3 class="font-semibold text-primary">Assign Tasks</h3>
    </div>
    <section class="p-3 mt-5">
        <!-- Top Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
            <!-- Pending -->
            <div class="bg-[#FFF6DE] rounded-xl shadow p-4 flex flex-col justify-between">
                <p class="font-semibold">Pending</p>
                <div class="flex items-center justify-between py-2">
                    <h3 class="text-3xl font-bold text-[#FF683D]">{{ $pending_tasks }}</h3>
                    <img src="{{ asset('/images/task_pending.png') }}" alt="Pending" class="w-14 h-14">
                </div>
            </div>
            <!-- Ongoing -->
            <div class="bg-[#FFEBE4] rounded-xl shadow p-4 flex flex-col justify-between">
                <p class="font-semibold">Ongoing</p>
                <div class="flex items-center justify-between py-2">
                    <h3 class="text-3xl font-bold text-[#F9C539]">{{ $ongoing_tasks  }}</h3>
                    <img src="{{ asset('/images/task_ongoing.png') }}" alt="Ongoing" class="w-14 h-14">
                </div>
            </div>
            <!-- Completed -->
            <div class="bg-[#DAECFF] rounded-xl shadow p-4 flex flex-col justify-between">
                <p class="font-semibold">Completed</p>
                <div class="flex items-center justify-between py-2">
                    <h3 class="text-3xl font-bold text-[#4CAF50]">{{ $completed_tasks }}</h3>
                    <img src="{{ asset('/images/task_completed.png') }}" alt="Completed" class="w-14 h-14">
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow p-5 mt-3">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="flex items-center bg-[#F2E8F5] rounded-full px-3">
                    <select name="status" class="bg-[#F2E8F5] w-full rounded-full px-4 py-2 focus:outline-none">
                        <option value="">Search Status</option>
                        <option value="low">low</option>
                        <option value="medium">medium</option>
                        <option value="high">high</option>
                    </select>
                </div>
                <!-- Status Filter -->
                <div class="bg-[#F2E8F5] rounded-full">
                    <select name="admin" class="bg-[#F2E8F5] w-full rounded-full px-4 py-2 focus:outline-none">
                        <option value="">Search Admin</option>
                        @foreach ($admins as $admin)
                            <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                        @endforeach
                    </select>
                </div>
                <!-- Event Filter -->
                <div class="bg-[#F2E8F5] rounded-full">
                    <select name="event" class="bg-[#F2E8F5] w-full rounded-full px-4 py-2 focus:outline-none">
                        <option value="">Search Event</option>
                        @foreach ($event as $eve)
                            <option value="{{ $eve->title }}">{{ $eve->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button id="refreshBtn" class="px-4 py-1 bg-[#F2E8F5] text-gray-700 rounded-full"> Refresh </button>
                </div>
            </div>
        </div>
        <div class="flex justify-end">
            <a href="{{ route('create_assign_task') }}"
                class="px-2 w-40 mt-3 bg-gradient-to-r from-primary to-pink-600 text-white font-medium py-1 rounded-full">
                <i class="fa fa-plus" aria-hidden="true"></i>Create Task</a>
        </div>
        <div class="taskdiv grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-5">
            @foreach ($tasks as $task)
                <div class="task-card bg-white rounded-2xl shadow hover:shadow-lg transition p-5 flex flex-col justify-between"
                    data-admin="{{ $task->get_admin->id ?? '' }}" data-status="{{ $task->priority ?? '' }}"
                    data-event="{{ $task->title ?? '' }}">
                    <!-- Header -->
                    <div class="flex items-start justify-between mb-2">
                        <p class="font-semibold text-lg text-primary truncate w-full" title="{{ $task->title ?? '' }}">
                            {{ $task->title ?? '' }}
                        </p>
                        <span class="text-sm bg-primary text-white px-3 py-1 rounded-full">
                            {{ ucfirst($task->priority ?? '') }}
                        </span>
                    </div>
                    <!-- Admin -->
                    <p class="text-sm text-gray-500 mb-2">By: {{ $task->get_admin->name ?? '' }}</p>
                    <!-- Description -->
                    <p class="text-sm truncate w-full mb-3">{{ $task->description ?? '' }}</p>
                    <!-- Status & Deadline -->
                    <div class="flex flex-wrap gap-2 mb-3 text-xs">
                        @if ($task->status == 'completed')
                            <button data-id="{{ $task->id }}"
                                class="accept flex items-center bg-gradient-to-r from-primary to-pink-600 rounded-full px-4 py-1 text-white">
                                Accept
                            </button>
                        @else
                            <div class="flex items-center bg-[#F2E8F5] rounded-full px-3 py-1">
                                <span class="ml-1">
                                    @if ($task->status == 'pending')
                                        <i class="fa fa-hourglass-half text-primary"></i>Pending
                                    @elseif($task->status == 'accepted')
                                    <div
                                class="flex items-center bg-gradient-to-r from-primary to-pink-600 rounded-full px-4 py-1 text-white">
                                Accepted
                            </div>
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
                        <a href="{{ route('create_assign_task', ['task_id' => encrypt($task->id)]) }}"
                            class="text-primary hover:text-primary-dark">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts.app>

<script src="{{ asset('admin/js/tasks.js') }}"></script>
