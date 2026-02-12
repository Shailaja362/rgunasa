<x-layouts.app>
    <div class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-6 py-4 flex justify-between items-center">
        <!-- Title & Subtitle -->
        <div class="flex flex-col">
            <h3 class="font-semibold text-primary text-lg">
                Create New Event
            </h3>
            <p class="text-sm text-gray-700">
                Set up a new mission event for student participation
            </p>
        </div>
        <!-- Back Button -->
        <a href="{{ route('event_list') }}" class="flex items-center text-gray-700 hover:text-primary transition-colors">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back
        </a>
    </div>
    <h1 class="text-primary font-semibold mt-10 px-3">Event Information</h1>
    <p class="px-3">Basic Details about your event</p>
    <form id="eventForm" method="POST" enctype="multipart/form-data" class="space-y-4 mt-8 px-3">
        @csrf
        <!-- Event Info Section -->
        @if (!empty($edit_event) && isset($edit_event))
            <input type="hidden" name="event_id" value={{ $edit_event->id }}>
        @endif
        <input type="hidden" id="task_id" name="task_id" value="{{ request()->task_id }}">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium">Event Title<span class="text-red-500">*</span></label>
                <input type="text" name="event_title" value="{{ $edit_event->title ?? '' }}" id="event_title"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>
            <div>
                <label class="block text-sm font-medium">Select Club<span class="text-red-600">*</span></label>
                <select name="club_id" id="club_id"
                    class="bg-[#D9D9D9] w-full rounded-full py-3 px-3 focus:outline-none focus:ring focus:ring-primary/40">
                    <option value="">Select Club</option>
                    @foreach ($club as $club_value)
                        <option value="{{ $club_value->id }}" @if (!empty($edit_event) && $edit_event->club_id == $club_value->id) selected @endif>
                            {{ $club_value->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium">Programme Officer<span class="text-red-600">*</span></label>
                <select name="programme_officer" id="programme_officer"
                    class="bg-[#D9D9D9] w-full rounded-full py-3 px-3 focus:outline-none focus:ring focus:ring-primary/40">
                    <option value="">Select Programme Officer</option>
                    @if (!empty($edit_faculty))
                        <option value="{{ $edit_faculty->id }}" selected>{{ $edit_faculty->name }}</option>
                    @endif
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium">
                    Event Duration (Months)<span class="text-red-500">*</span>
                </label>
                <input type="number" name="duration_months" min="1" id="duration_months"
                    value="{{ $edit_event->duration_months ?? '' }}"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>

            <div>
                <label class="block text-sm font-medium">Event Description<span class="text-red-600">*</span></label>
                <textarea name="description" id="description"
                    class="bg-[#D9D9D9] w-full p-3 border border-gray-300 rounded-2xl focus:outline-none focus:ring focus:ring-primary/40"
                    rows="4"
                    placeholder="Provide a detailed description of the event objective, activities, and learning outcomes">
                        {{ $edit_event->description ?? '' }}
                </textarea>
            </div>

            <div>
                <label class="block text-sm font-medium">
                    Is Technical Event <span class="text-red-500">*</span>
                </label>

                <div class="flex items-center gap-6 mt-2">

                    {{-- YES --}}
                    <label class="inline-flex items-center">
                        <input type="radio" name="is_technical_event" value="y"
                            class="text-primary focus:ring-primary is_technical_event"
                            {{ old('is_technical_event', optional($edit_event)->is_technical_event) === 'y' ? 'checked' : '' }}>
                        <span class="ml-2 text-sm">Yes</span>
                    </label>

                    {{-- NO --}}
                    <label class="inline-flex items-center">
                        <input type="radio" name="is_technical_event" value="n"
                            class="text-primary focus:ring-primary is_technical_event"
                            {{ old('is_technical_event', optional($edit_event)->is_technical_event) === 'n' ? 'checked' : '' }}>
                        <span class="ml-2 text-sm">No</span>
                    </label>

                </div>
            </div>


            <div>
                <label class="block text-sm font-medium">Event Type <span class="text-red-600">*</span></label>
                <select name="event_type" id="event_type"
                    class="bg-[#D9D9D9] w-full rounded-full py-3 px-3 focus:outline-none focus:ring focus:ring-primary/40">
                    <option value="">Select Event Type</option>
                    <option value="paid" @if (!empty($edit_event) && $edit_event->event_type == 'paid') selected @endif>Paid</option>
                    <option value="free" @if (!empty($edit_event) && $edit_event->event_type == 'free') selected @endif>Free</option>
                </select>
            </div>

            <div id="priceFieldContainer">
                @if (!empty($edit_event) && $edit_event->event_type == 'paid')
                    <label class="block mt-3 font-medium">Price</label>
                    <input type="number" name="price" id="price" value="{{ $edit_event->price }}"
                        placeholder="Enter price"
                        class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40"
                        required>
                @endif
            </div>
        </div>
        <h1 class="text-primary font-semibold mt-10">Department-wise Schedule</h1>
        <p>Add one or more department schedules</p>
        <div id="departmentContainer" class="space-y-6 mt-6">
            @php
                if (!empty($edit_event) && $edit_event->get_dep_events->count() > 0) {
                    $deptData = $edit_event->get_dep_events;
                } else {
                    $deptData = collect([null]);
                }
            @endphp

            @foreach ($deptData as $index => $dept)
                <div class="bg-[#F0F0F0] p-5 rounded-2xl relative dept-card">
                    <input type="hidden" name="departments[{{ $index }}][schedule_id]"
                        value="{{ $dept->id ?? '' }}">
                    @if ($index > 0)
                        <button type="button"
                            class="rounded-2xl py-1 px-2 absolute top-2 right-5 text-red-500 font-bold removeDept bg-white">
                            Remove
                        </button>
                    @endif
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-5">
                        <div>
                            <label class="block text-sm font-medium">
                                Programme <span class="text-red-500">*</span>
                            </label>
                            <select name="departments[{{ $index }}][programme_id]"
                                class="bg-[#D9D9D9] w-full p-2 border border-gray-300 rounded-full focus:outline-none focus:ring focus:ring-primary/40 department">
                                <option value="">Select Programme</option>
                                @foreach ($programmes as $d)
                                    <option value="{{ $d->id }}"
                                        @if (!empty($dept) && $dept->programme_id == $d->id) selected @endif>
                                        {{ $d->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Section --}}
                        <div>
                            <label class="block text-sm font-medium"> Section <span
                                    class="text-red-500">*</span></label>
                            <select name="departments[{{ $index }}][section]" id="section"
                                class="bg-[#D9D9D9] w-full p-2 border border-gray-300 rounded-full focus:outline-none focus:ring focus:ring-primary/40 section">
                                <option value="" selected disabled>Select Section</option>
                                <option value="a" {{ $dept?->section == 'a' ? 'selected' : '' }}>A</option>
                                <option value="b" {{ $dept?->section == 'b' ? 'selected' : '' }}>B</option>
                                <option value="c" {{ $dept?->section == 'c' ? 'selected' : '' }}>C</option>
                                <option value="d" {{ $dept?->section == 'd' ? 'selected' : '' }}>D</option>
                                <option value="r" {{ $dept?->section == 'r' ? 'selected' : '' }}>R</option>
                            </select>
                        </div>
                        {{-- Event Date --}}
                        <div>
                            <label class="block text-sm font-medium">Event Date <span
                                    class="text-red-600">*</span></label>
                            <input type="text" name="departments[{{ $index }}][event_date]"
                                value="{{ old(
                                    "departments.$index.event_date",
                                    optional($dept)->event_date ? \Carbon\Carbon::parse($dept->event_date)->format('d/m/Y') : '',
                                ) }}"
                                class="date_field bg-[#D9D9D9] w-full p-2 border border-gray-300 rounded-full focus:outline-none focus:ring focus:ring-primary/40 event_date">

                        </div>
                        {{-- Reserve Date --}}
                        <div>
                            <label class="block text-sm font-medium">
                                Is Reserve Date
                            </label>

                            <div class="flex items-center gap-6 mt-2">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="departments[{{ $index }}][is_reserve_date]"
                                        value="y" class="text-primary focus:ring-primary is_reserve_date"
                                        {{ old("departments.$index.is_reserve_date", optional($dept)->is_reserve_date) === 'y' ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm">Yes</span>
                                </label>

                                <label class="inline-flex items-center">
                                    <input type="radio" name="departments[{{ $index }}][is_reserve_date]"
                                        value="n" class="text-primary focus:ring-primary is_reserve_date"
                                        {{ old("departments.$index.is_reserve_date", optional($dept)->is_reserve_date) === 'n' ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm">No</span>
                                </label>
                            </div>
                        </div>

                        {{-- Seat Count --}}
                        <div>
                            <label class="block text-sm font-medium">Seat Count <span
                                    class="text-red-600">*</span></label>
                            <input type="number" name="departments[{{ $index }}][seat_count]"
                                value="{{ $dept->seat_count ?? '' }}"
                                class="bg-[#D9D9D9] w-full p-2 border border-gray-300 rounded-full focus:outline-none focus:ring focus:ring-primary/40 seat_count">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" id="addDeptBtn" class="px-4 py-2 bg-primary text-white rounded-full mt-4">
            + Add Department
        </button>
        <!-- Schedule Section -->
        <h1 class="text-primary font-semibold mt-10 px-3">Schedule & Location</h1>
        <p class="px-3">When and where the event will take place</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6 px-3">
            <div>
                <label class="block text-sm font-medium">Start Time<span class="text-red-600">*</span></label>
                <input type="time" name="start_time" id="start_time" value="{{ $edit_event->start_time ?? '' }}"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>
            <div>
                <label class="block text-sm font-medium">End Time<span class="text-red-600">*</span></label>
                <input type="time" name="end_time" id="end_time" value="{{ $edit_event->end_time ?? '' }}"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>
            <div>
                <label class="block text-sm font-medium">Reserve Start Time</label>
                <input type="time" name="reserve_start_time" id="reserve_start_time"
                    value="{{ $edit_event->reserve_start_time ?? '' }}"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>
            <div>
                <label class="block text-sm font-medium">Reserve End Time</label>
                <input type="time" name="reserve_end_time" id="reserve_end_time"
                    value="{{ $edit_event->reserve_end_time ?? '' }}"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium">Location / Virtual Link<span
                        class="text-red-600">*</span></label>
                <textarea name="location" id="location"
                    class="bg-[#D9D9D9] w-full p-3 border border-gray-300 rounded-2xl focus:outline-none focus:ring focus:ring-primary/40"
                    rows="3" placeholder="Enter the event venue or virtual meeting link">{{ $edit_event->location ?? '' }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium">Session<span class="text-red-600">*</span></label>
                <select name="session" id="session"
                    class="bg-[#D9D9D9] w-full rounded-full py-3 px-3 focus:outline-none focus:ring focus:ring-primary/40">
                    <option value="">Select Session</option>
                    <option value="1" @if (!empty($edit_event) && $edit_event->session == 1) selected @endif>FN</option>
                    <option value="2" @if (!empty($edit_event) && $edit_event->session == 2) selected @endif>AN</option>
                </select>
            </div>
        </div>

        <!-- Registration Details -->
        <h1 class="text-primary font-semibold mt-10 px-3">Registration Details</h1>
        <p class="px-3">Eligibility and registration requirements</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium">Eligibility Criteria<span
                        class="text-red-600">*</span></label>
                <textarea name="eligibility" id="eligibility"
                    class="bg-[#D9D9D9] w-full p-3 border border-gray-300 rounded-2xl focus:outline-none focus:ring focus:ring-primary/40"
                    rows="4" placeholder="e.g., Engineering students, Grade 10-12, Previous workshop attendance required.">{{ $edit_event->eligibility_criteria ?? '' }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium">Registration Deadline<span
                        class="text-red-500">*</span></label>
                <input type="text" name="registration_deadline" id="registration_deadline"
                    value="{{ $edit_event ? \Carbon\Carbon::parse($edit_event->end_registration)->format('d/m/Y') : '' }}"
                    class="date_field w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>

            <div>
                <label class="block text-sm font-medium">Contact Person<span class="text-red-500">*</span></label>
                <input type="text" name="contact_person" placeholder="Full name of Contact Person"
                    id="contact_person" value="{{ $edit_event->contact_person ?? '' }}"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>

            <div>
                <label class="block text-sm font-medium">Contact Email<span class="text-red-500">*</span></label>
                <input type="email" name="contact_email" placeholder="contact@example.com" id="contact_email"
                    value="{{ $edit_event->contact_email ?? '' }}"
                    class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>
        </div>

        <!-- Additional Details -->
        <h1 class="text-primary font-semibold mt-10 px-3">Additional Details</h1>
        <p class="px-3">Banner image and additional notes</p>

        <div class="grid grid-cols-1">
            <div class="bg-[#F0F0F0] rounded-3xl p-10 text-center relative">
                <div id="uploadBox" class="space-y-8">
                    <div id="dropArea" class="border-2 border-dashed border-primary rounded-2xl p-8 relative">
                        {{-- Preview Area --}}
                        <div id="previewArea" class="flex justify-center">
                            @if (!empty($edit_event->banner_image))
                                <img src="{{ asset('storage/' . $edit_event->banner_image) }}"
                                    class="mx-auto rounded-2xl w-40 h-40 object-cover" />
                                <input type="hidden" name="old_banner" value="{{ $edit_event->banner_image }}">
                            @endif
                        </div>
                        {{-- Upload Text (Hide if image exists) --}}
                        <div id="uploadText" @if (!empty($edit_event->banner_image)) style="display:none" @endif>
                            <img src="{{ asset('/images/upload.png') }}" id="banner" class="mx-auto w-14 mb-3" />
                            <p class="text-primary font-semibold">Upload event banner image</p>
                            <p class="text-primary text-sm mt-2">PNG, JPG up to 5MB</p>
                        </div>
                        {{-- File Input --}}
                        <input type="file" id="fileInput" name="banner_image" accept="image/*" class="hidden" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Center-Aligned Button -->
        <div class="flex justify-center mt-10">
            <button type="submit" id="submitBtn"
                class="px-3 w-43 bg-gradient-to-r from-primary to-pink-600 text-white font-semibold py-1 rounded-full hover:opacity-90 transition">
                <i class="fas fa-save"></i> Create Event </button>
        </div>
    </form>
</x-layouts.app>

<script>
    const deptOptions = @json(
        $programmes->map(function ($d) {
            return ['id' => $d->id, 'name' => $d->name];
        }));
    flatpickr(".date_field", {
        dateFormat: "d/m/Y",
    });
</script>
<script src="{{ asset('admin/js/events.js') }}?v={{ time() }}"></script>
