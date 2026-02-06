<x-layouts.app>
    <!-- Header -->
    <div class="bg-[#F5E8F5] w-full h-[90px] rounded-full shadow-sm px-8 py-3 flex flex-col justify-center">
        <h3 class="font-semibold text-primary">Student Approval Management</h3>
        <p class="text-sm text-gray-700">Review and manage student event applications</p>
    </div>

    <!-- Overview Cards -->
    <section class="p-3 mt-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Total Applications -->
            <div class="bg-[#F3EDFF] rounded-2xl shadow p-5 flex flex-col justify-between">
                <p class="font-semibold text-gray-700 text-sm">Total Applied Event</p>
                <div class="flex items-center justify-between py-4">
                    <h3 class="text-3xl font-bold text-[#FF4757]">{{ $total_applied_event }}</h3>
                    <img src="{{ asset('/images/total_applications.png') }}" alt="Total Applications" class="w-14 h-14">
                </div>
            </div>

            <!-- Pending -->
            <div class="bg-[#FFECE4] rounded-2xl shadow p-5 flex flex-col justify-between">
                <p class="font-semibold text-gray-700 text-sm">Pending</p>
                <div class="flex items-center justify-between py-4">
                    <h3 class="text-3xl font-bold text-[#F9C539]">{{ $pending }}</h3>
                    <img src="{{ asset('/images/task_pending.png') }}" alt="Pending" class="w-14 h-14">
                </div>
            </div>

            <!-- Approved -->
            <div class="bg-[#DAECFF] rounded-2xl shadow p-5 flex flex-col justify-between">
                <p class="font-semibold text-gray-700 text-sm">Approved</p>
                <div class="flex items-center justify-between py-4">
                    <h3 class="text-3xl font-bold text-[#21FFD4]">{{ $present }}</h3>
                    <img src="{{ asset('/images/approved.png') }}" alt="Approved" class="w-14 h-14">
                </div>
            </div>

            <!-- Rejected -->
            <div class="bg-[#FFF6DE] rounded-2xl shadow p-5 flex flex-col justify-between">
                <p class="font-semibold text-gray-700 text-sm">Rejected</p>
                <div class="flex items-center justify-between py-4">
                    <h3 class="text-3xl font-bold text-[#FF683D]">{{ $absent }}</h3>
                    <img src="{{ asset('/images/rejected.png') }}" alt="Rejected" class="w-14 h-14">
                </div>
            </div>
        </div>
        <!-- Verified Students -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-5">
            @foreach ($registeredEvents as $event)
                @php
                    $getproof = $event->get_student_proof->where('event_id', $event->event_id)->first();
                @endphp
                <div class="task-card bg-white rounded-2xl shadow hover:shadow-lg transition p-5 flex flex-col justify-between"
                    data-student="{{ $event->student_id ?? '' }}" data-status="{{ $event->status == 2 ? 2 : 1 }}"
                    data-event="{{ $event->event->title ?? '' }}">


                    <!-- Header -->
                    <div class="flex items-center justify-between">
                        <p class="font-semibold text-lg text-gray-800">{{ $event->student->name ?? '' }}</p>
                        @if ($event->status == 2)
                            <span class="text-sm bg-[#21C45D] text-white px-3 py-1 rounded-full">Verified</span>
                        @else
                            <span class="text-sm bg-[#FF5050] text-white px-3 py-1 rounded-full">Not Verified</span>
                        @endif
                    </div>

                    <!-- ID -->
                    <p class="text-sm mt-1 border border-primary px-2 py-1 rounded-full w-40 inline-block">ID:
                        {{ $event->student->id ?? '' }}</p>
                    <p class="text-sm mt-1"><span class="text-primary">•</span> Submitted:
                        {{ \Carbon\Carbon::parse($event->created_at)->format('d-m-Y') }}</p>

                    <!-- Image -->
                    <div class="relative mt-3">
                        @if (!empty($getproof))
                            <img src="{{ asset('storage/' . $getproof->file_path) }}"
                                class="rounded-xl w-full h-[180px] object-cover">
                        @else
                            <div
                                class="bg-[#EDEDED] mt-3 flex flex-col justify-center items-center h-[180px] rounded-xl">
                                <img src="{{ asset('/images/not_uploaded.png') }}" class="w-16 h-16 opacity-70 mb-2">
                                <p class="text-red-600 text-sm">No image uploaded</p>
                            </div>
                        @endif
                        <span
                            class="absolute top-3 left-3 bg-[rgba(128,128,128,0.8)] text-white text-xs px-3 py-1 rounded-full">
                            {{ $event->event->title }}
                        </span>
                    </div>

                    <!-- Buttons -->
                    <div class="grid grid-cols-2 gap-3 mt-4">

                        {{-- Present Button --}}
                        <button data-id="{{ $event->id }}" data-action="2"
                            class="btnAction w-full font-medium py-1 rounded-full
                     {{ $getproof ? 'bg-gradient-to-r from-primary to-pink-600 text-white' : 'text-white bg-gradient-to-r from-primary to-pink-300 cursor-not-allowed' }}"
                            {{ $getproof ? '' : 'disabled' }}>
                            <i class="fa fa-check-circle mr-1"></i> Present
                        </button>

                        {{-- Absent Button --}}
                        <button data-id="{{ $event->id }}" data-action="4"
                            class="btnAction w-full bg-gradient-to-r from-primary to-pink-600 text-white font-medium py-1 rounded-full">
                            <i class="fa fa-times-circle mr-1"></i> Absent
                        </button>

                    </div>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts.app>

<script src="{{ asset('admin/js/student_approval.js') }}?v={{ time() }}"></script>
