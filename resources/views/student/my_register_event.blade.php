<x-layouts.app>
    <section class="p-2">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-[#BF9CFF] rounded-2xl shadow p-5 flex flex-col justify-between">
                <p class="text-[#230060] font-medium">Active Registration</p>
                <div class="flex items-center justify-between">
                    <h3 class="text-3xl font-bold text-[#230060]">{{ $activeCount ?? 0 }}</h3>
                    <img src="{{ asset('/images/active_registration.png') }}" alt=""
                        class="mx-auto mt-[-40px] mb-[-40px]">
                </div>
                <div class="relative">
                    {{-- <span class="bg-white text-xs px-4 py-1 rounded-full">Available this Semester</span> --}}
                </div>
            </div>
            <div class="bg-[#FF8F6B] rounded-2xl shadow p-5 flex flex-col justify-between">
                <p class="text-[#992B07] font-medium">Attended Registration</p>
                <div class="flex items-center justify-between mt-2">
                    <h3 class="text-3xl font-bold text-[#992B07]">{{ $attendedCount ?? 0 }}</h3>
                    <img src="{{ asset('/images/attended_events.png') }}" alt="" class="mx-auto">
                </div>
                <div class="relative">
                    <span class="bg-white text-xs px-4 py-1 rounded-full">Upcoming & Ongoing</span>
                </div>
            </div>

            <div class="bg-[#B5DAFF] rounded-2xl shadow p-5 flex flex-col justify-between">
                <p class=" text-[#0756A6] font-medium">Pending Uploads</p>
                <div class="flex items-center justify-between mt-2">
                    <h3 class="text-3xl font-bold text-[#0756A5]">{{ $pending_uploads ?? 0 }}</h3>
                    <img src="{{ asset('/images/pending_uploads.png') }}" alt="" class="mx-auto">
                </div>
                <div class="relative">
                    {{-- <span class="bg-white text-xs px-4 py-1 rounded-full">This Academic Year</span> --}}
                </div>
            </div>
        </div>
        <div class="bg-[#F2E8F5] rounded-full px-5 py-3 mt-8 flex justify-between items-center">
            <h3 class="font-semibold text-primary">Events</h3>
            <div class="flex space-x-4 text-gray-700 font-medium">
                <span id="registered-tab" class="cursor-pointer bg-white px-3 text-primary rounded-full transition py-1"
                    onclick="showMyregistration('registered')">Registered</span>
                <span id="completed-tab" class="cursor-pointer px-3 rounded-full transition py-1"
                    onclick="showMyregistration('completed')">Completed</span>
            </div>
        </div>
        <div id="registered-section" class="mt-6">
            <h4 class="font-semibold text-gray-800 mb-4">Registered Events</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($registeredEvents as $event)
                    @php
                        $registered = \App\Models\StudentEventRegistration::where([
                            'event_schedule_id' => $event->event_schedule_id,
                        ])
                            ->whereHas('student', function ($query) use ($student) {
                                $query
                                    ->where('programme_id', $student->programme_id)
                                    ->where('semester', $student->semester)
                                    ->where('batch', $student->batch)
                                    ->where('section', $student->section);
                            })
                            ->count();
                        if ($event->get_event_schedule->is_reserve_date == 'y') {
                            $start_time = $event->event->reserve_start_time;
                            $end_time = $event->event->reserve_end_time;
                        } else {
                            $start_time = $event->event->start_time;
                            $end_time = $event->event->end_time;
                        }
                        $available = $event->get_event_schedule
                            ? $event->get_event_schedule->seat_count - $registered
                            : 0;
                    @endphp
                    <div class="bg-white rounded-2xl shadow hover:shadow-lg transition">
                        <div class="relative">
                            <img src="{{ asset('storage/' . $event->event->banner_image) }}" alt="Event"
                                class="rounded-t-2xl w-full h-48 object-cover">
                            @if ($event->event->event_type == 'paid')
                                <span
                                    class= "absolute top-3 right-3 bg-[#FFC31F] text-white px-3 text-sm py-1 rounded-full">
                                    Premium
                                </span>
                            @endif
                            <span
                                class="absolute @if ($event->event->event_type == 'paid') mt-2 top-10 @else top-3 @endif  right-3 bg-gradient-to-r from-primary to-pink-600 text-white px-3 text-sm py-1 rounded-full">
                                <span class="text-2xl">{{ $available }} </span><span>Seats
                                    <pre> Available</span></pre>
                                </span>
                            </span>
                            <span
                                class="absolute bottom-3 left-3 bg-[rgba(128,128,128,0.4)] text-white text-xs px-3 py-1 rounded-full">
                                {{ $event->event->title }}
                            </span>
                        </div>
                        <div class="p-2 details">
                            <div class="grid grid-cols-1 gap-1 md:grid-cols-4 text-xs">
                                <div class="col-span-2 flex items-center bg-[#F2E8F5] rounded-full px-1 py-1">
                                    <i class="fa fa-clock text-primary" aria-hidden="true"></i>
                                    <p class="px-1">
                                        {{ $start_time ? \Carbon\Carbon::parse($start_time)->format('h:i A') : '-' }}
                                        -
                                        {{ $end_time ? \Carbon\Carbon::parse($end_time)->format('h:i A') : '-' }}
                                    </p>
                                </div>
                                <div class="col-span-2 flex items-center bg-[#F2E8F5] rounded-full px-2 py-1">
                                    <i class="fa fa-calendar text-primary" aria-hidden="true"></i>
                                    <p class="px-1">
                                        {{ optional($event->get_event_schedule)->event_date ? \Carbon\Carbon::parse($event->get_event_schedule->event_date)->format('F j, Y') : '-' }}
                                    </p>
                                </div>
                                <div class="col-span-4 flex items-center bg-[#F2E8F5] rounded-full px-1 py-1 mt-2">
                                    <i class="fa fa-map-marker text-primary" aria-hidden="true"></i>
                                    <p class="px-2">{{ $event->event->location }}</p>
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
                @foreach ($completedEvents as $event)
                    @php
                        $registered = \App\Models\StudentEventRegistration::where([
                            'event_schedule_id' => $event->event_schedule_id,
                        ])
                            ->whereHas('student', function ($query) use ($student) {
                                $query
                                    ->where('programme_id', $student->programme_id)
                                    ->where('section', $student->section)
                                    ->where('semester', $student->semester)
                                    ->where('batch', $student->batch);
                            })
                            ->count();
                        if ($event->get_event_schedule->is_reserve_date == 'y') {
                            $start_time = $event->event->reserve_start_time;
                            $end_time = $event->event->reserve_end_time;
                        } else {
                            $start_time = $event->event->start_time;
                            $end_time = $event->event->end_time;
                        }
                        $available = $event->get_event_schedule->seat_count - $registered;
                    @endphp
                    <div class="bg-white rounded-2xl shadow hover:shadow-lg transition">
                        <div class="relative">
                            <img src="{{ asset('storage/' . $event->event->banner_image) }}" alt="Event"
                                class="rounded-t-2xl w-full h-48 object-cover">
                            <span
                                class="absolute top-3 right-3 bg-gradient-to-r from-primary to-pink-600 text-white px-3 text-sm py-1 rounded-full">
                                <span class="text-2xl">{{ $available }}</span> Seats Available
                            </span>
                            <span
                                class="absolute bottom-3 left-3 bg-[rgba(128,128,128,0.4)] text-white text-xs px-3 py-1 rounded-full">
                                {{ $event->event->title }}
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
                                <div class="col-span-2 flex items-center bg-[#F2E8F5] rounded-full px-2 py-1">
                                    <i class="fa fa-calendar text-primary" aria-hidden="true"></i>
                                    <p class="px-1">
                                        {{ optional($event->get_event_schedule)->event_date ? \Carbon\Carbon::parse($event->get_event_schedule->event_date)->format('F j, Y') : '-' }}
                                    </p>
                                </div>
                                <div class="col-span-4 flex items-center bg-[#F2E8F5] rounded-full px-1 py-1 mt-2">
                                    <i class="fa fa-map-marker text-primary" aria-hidden="true"></i>
                                    <p class="px-1">{{ $event->event->location }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 text-xs mt-2">

                                <!-- View Details Button -->
                                <div class="flex border border-primary items-center justify-center rounded-full px-5 py-1 view-details-btn cursor-pointer hover:bg-primary hover:text-white transition text-primary"
                                    data-event_id="{{ $event->event->id }}" data-title="{{ $event->event->title }}"
                                    data-image="{{ asset('storage/' . $event->event->banner_image) }}"
                                    data-description="{{ $event->event->description }}"
                                    data-date="{{ \Carbon\Carbon::parse($event->get_event_schedule->event_date)->format('F j, Y') }}"
                                    data-start="{{ \Carbon\Carbon::parse($start_time)->format('h:i A') }}"
                                    data-end="{{ \Carbon\Carbon::parse($end_time)->format('h:i A') }}"
                                    data-location="{{ $event->event->location }}">
                                    <p class="px-2 text-center">
                                        View Details
                                    </p>
                                </div>
                                <!-- Upload Proof Button -->
                                <div class="flex border border-primary text-primary items-center justify-center rounded-full px-5 py-1 upload cursor-pointer hover:bg-primary hover:text-white transition"
                                    data-event_id="{{ $event->event->id }}"
                                    data-schedule_id="{{ $event->get_event_schedule->id }}"
                                    data-student_id="{{ $event->student_id }}"
                                    data-is_technical="{{ $event->event->is_technical_event }}">
                                    <p class="px-2 text-center">
                                        Upload Proof
                                    </p>
                                </div>

                            </div>

                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <div id="viewDetailsModal"
        class="fixed inset-0 hidden z-50 bg-[rgba(128,128,128,0.4)] bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-lg w-11/12 md:w-1/2 relative">
            <button id="closeModal"
                class="closeModal absolute bg-[rgba(128,128,128,0.6)] w-5 rounded-full top-3 right-3 text-gray-300 hover:text-white">&times;</button>
            <div id="modalContent">
                <img id="modalImage" src="" alt="Event Image" class="w-full h-60 object-cover rounded-xl">
                <div class="px-3 py-3">
                    <h3 id="modalTitle" class="text-xl text-primary mb-2"></h3>
                    <p id="modalDescription" class="text-sm mb-2"></p>
                    <div class="grid grid-cols-1 gap-1 md:grid-cols-3 text-xs py-3">
                        <div class="flex items-center bg-[#F2E8F5] rounded-full px-2 py-1">
                            <i class="fa fa-clock text-primary" aria-hidden="true"></i>
                            <p class="px-2" id="modalTime">
                            </p>
                        </div>
                        <div class="flex items-center bg-[#F2E8F5] rounded-full px-2 py-1">
                            <i class="fa fa-calendar text-primary" aria-hidden="true"></i>
                            <p class="px-2" id="modalDate"></p>
                        </div>
                        <div class="flex items-center bg-[#F2E8F5] rounded-full px-2 py-1">
                            <i class="fa fa-map-marker text-primary" aria-hidden="true"></i>
                            <p class="px-2" id="modalLocation"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="uploadModal"
        class="fixed inset-0 bg-black/60 z-50 hidden overflow-y-auto flex items-start justify-center px-4 py-6">
        <div class="bg-gradient-to-r from-[#C068EE] to-[#9D55EC] rounded-3xl p-10 text-center w-[500px] relative">
            <!-- Close Button -->
            <button id="closeModal"
                class="closeModal absolute top-4 right-6 text-white text-xl font-bold">&times;</button>
            <input id="event_id" type="hidden" />
            <input id="student_id" type="hidden" />
            <input id="schedule_id" type="hidden" />
            <!-- Upload Box -->
            <div id="uploadBox" class="space-y-6">
                <div id="dropArea"
                    class="border-2 border-dashed border-white rounded-2xl p-8 cursor-pointer relative">
                    <div id="previewArea" class="hidden grid grid-cols-2 gap-4"></div>
                    <div id="uploadText">
                        <img src="{{ asset('/images/upload.png') }}" class="mx-auto w-14 mb-3" />
                        <p class="text-white font-semibold">Drag & drop your images here</p>
                        <p class="text-white text-sm mt-2">or click to select files</p>
                        <p class="text-[#FD5063] text-md mt-2 py-2 rounded-full bg-white">
                            JPG, PNG, PDF, Word, Excel — Max 10MB each • Max 4 files
                        </p>
                    </div>
                    <input type="file" id="fileInput" multiple class="hidden" />
                </div>
                <!-- Divider -->
                <div class="my-6 border-t border-white/30"></div>
                <!-- ================= Feedback Section ================= -->
                <div class="bg-white/10 rounded-2xl p-5 text-left">
                    <h4 class="text-white font-semibold mb-4">Event Feedback</h4>
                    <div id="feedbackContainer"></div>
                    <label class="text-white text-sm block mt-4 mb-2">
                        Key Take Away <span class="text-red-600">*</span>
                    </label>
                    <textarea name="comments" id="comments" rows="3" class="w-full rounded-xl p-3 text-sm"
                        placeholder="Any memorable moment or suggestion?"></textarea>
                </div>
                <button id="submitUpload"
                    class="bg-gradient-to-r from-primary to-pink-600 text-white px-6 py-2 rounded-full">
                    Submit Here
                </button>
            </div>

            <!-- Success Box -->
            <div id="successBox" class="hidden">
                <div class="flex flex-col items-center space-y-4">
                    <img src="{{ asset('/images/upload_sucessfull.png') }}" width="70" />
                    <p class="text-white font-semibold">Proof uploaded successfully!</p>
                    <p class="text-white text-sm">Your attendance is marked as present</p>
                    {{-- <button id="uploadAnother"
                        class="upload border border-white text-white px-6 py-2 rounded-full mt-3">
                        Upload another proof
                    </button> --}}
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
<script src="{{ asset('admin/js/myregistration.js') }}?v={{ time() }}"></script>
