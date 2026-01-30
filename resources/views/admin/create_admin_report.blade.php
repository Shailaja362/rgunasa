<x-layouts.app>
    <!-- Header -->
    <div class="bg-[#F5E8F5] w-full h-[70px] rounded-full shadow-sm px-8 py-3">
        <h3 class="font-semibold text-primary">Event Report Submission</h3>
        <p>Submit comprehensive reports for completed events</p>
    </div>

    <form id="eventReportForm" action="{{ route('student_register_event') }}" method="POST" enctype="multipart/form-data"
        class="mt-8 px-4">
        @csrf
        <!-- EVENT INFORMATION -->
        <h2 class="text-primary font-semibold mt-10 px-4">Event Information</h2>
        <p class="px-4 text-gray-600 text-sm">Select the completed events and confirm details</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 px-4 mt-4">
            <div>
                <label class="block text-sm font-medium">Event<span class="text-red-600">*</span></label>
                <select name="event_id" id="event_id"
                    class="bg-[#D9D9D9] w-full rounded-full px-4 mt-1 py-3 focus:outline-none focus:ring-2 focus:ring-primary/40 focus:outline-none">
                    <option value="">Search Event</option>
                    @foreach ($event as $eve)
                        <option value="{{ $eve->id }}">{{ $eve->title }}</option>
                    @endforeach
                </select>
            </div>
             <div>
                            <label class="block text-sm font-medium">
                                Department <span class="text-red-500">*</span>
                            </label>
                            <select name="department_id"
                                class="bg-[#D9D9D9] w-full p-2 border border-gray-300 rounded-full focus:outline-none focus:ring focus:ring-primary/40 department">
                                <option value="">Select Department</option>
                                @foreach ($departments as $d)
                                    <option value="{{ $d->id }}"
                                        @if (!empty($eve) && $eve->id == $d->id) selected @endif>
                                        {{ $d->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
            <div>
                <label class="block text-sm font-medium">Event Date <span class="text-red-600">*</span></label>
                <input type="date" name="event_date" id="event_date"
                    class="bg-[#D9D9D9] w-full rounded-full py-3 px-4 mt-1 focus:outline-none focus:ring-2 focus:ring-primary/40">
            </div>
        </div>
        <!-- PARTICIPATION SUMMARY -->
        <h2 class="text-primary font-semibold mt-10 px-4">Participation Summary</h2>
        <p class="px-4 text-gray-600 text-sm">Details about event attendance and participants</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 px-4 mt-4">
            <div>
                <label class="block text-sm font-medium">Male Count <span class="text-red-600">*</span></label>
                <input type="number" name="male_count" id="male_count" placeholder="0"
                    class="bg-[#D9D9D9] w-full rounded-full py-3 px-4 mt-1 focus:outline-none focus:ring-2 focus:ring-primary/40">
            </div>
            <div>
                <label class="block text-sm font-medium">Female Count <span class="text-red-600">*</span></label>
                <input type="number" id="female_count" name="female_count" placeholder="0"
                    class="bg-[#D9D9D9] w-full rounded-full py-3 px-4 mt-1 focus:outline-none focus:ring-2 focus:ring-primary/40">
            </div>
        </div>
        <!-- EVENT OUTCOMES -->
        <h2 class="text-primary font-semibold mt-10 px-4">Event Outcomes</h2>
        <div class="px-4 mt-2">
            <label class="block text-sm font-medium">Outcomes & Results <span class="text-red-600">*</span></label>
            <textarea name="outcome_results" id="outcome_results" rows="4"
                placeholder="Describe the main achievements, successful activities, notable moments, learning outcomes..."
                class="bg-[#D9D9D9] w-full rounded-2xl p-3 mt-1 focus:outline-none focus:ring-2 focus:ring-primary/40"></textarea>
        </div>
        <!-- EVENT PROOF IMAGES -->
        <h2 class="text-primary font-semibold mt-10 px-4">Geo Tag Images</h2>
        <div id="dropArea" class="border-2 border-dashed border-[#E54590] rounded-2xl p-6 text-center cursor-pointer"
            style="min-height:120px; display:flex; align-items:center; justify-content:center; flex-direction:column;">
            <div id="dropHint">
                <img src="{{ asset('/images/upload.png') }}" class="mx-auto w-14 mb-3" />
                <p class="font-medium text-[#E54590]">Upload event banner Image</p>
                <p class="text-sm text-[#E54590] mt-2 py-1 rounded-full bg-gray-100">
                    PNG ,JPG up to 5MB
                </p>
            </div>
        </div>

        <!-- Preview area -->
        <div id="previewArea" class="grid grid-cols-2 gap-4 mt-4">
            {{-- SHOW EXISTING IMAGES --}}
            @if (!empty($edit_task) && !empty($edit_task->get_task_images))
                @foreach ($edit_task->get_task_images as $img)
                    <div class="img-wrapper relative inline-block" data-existing="{{ $img['id'] }}">
                        <img src="{{ asset('storage/' . $img['file_path']) }}"
                            class="rounded-lg w-full h-32 object-cover" alt="heeee" />
                        <button type="button"
                            class="remove-img absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 text-sm">&times;</button>
                        <p class="text-gray-800 text-xs truncate w-[120px]">{{ $img['filename'] }}</p>
                    </div>
                @endforeach
            @endif
            <input type="hidden" id="removedImages" name="removed_images">
        </div>
        <!-- Hidden input -->
        <input id="fileInput" name="proof[]" type="file" accept="image/*" multiple class="hidden">

        <!-- FEEDBACK SUMMARY -->
        <h2 class="text-primary font-semibold mt-10 px-4">Feedback Summary <span class="text-red-500">*</span></h2>
        <div class="px-4 mt-2">
            <textarea name="feedback_summary" id="feedback_summary" rows="3"
                placeholder="Summarize participant feedback, ratings, testimonials, suggestions for improvement, and satisfaction levels..."
                class="bg-[#D9D9D9] w-full rounded-2xl p-3 mt-1 focus:outline-none focus:ring-2 focus:ring-primary/40"></textarea>
        </div>
        <!-- SUPPORTING FILES -->
        <h2 class="text-primary font-semibold mt-10 px-4">Supporting Files</h2>
        <p class="px-4 text-gray-600 text-sm">Upload images, certificates, documents, and other supporting materials</p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 px-4 mt-4">

            <!-- Certificate -->
            <div class="upload-box border-2 border-dashed border-primary rounded-2xl p-6 text-center"
                data-input="certificates" data-type="certificate">
                <img src="{{ asset('/images/upload.png') }}" class="mx-auto w-10 mb-2" />
                <p class="text-primary font-semibold">Upload Certificates</p>
                <input type="file" name="certificates" class="hidden proof_files" id="certificates">
                <div class="preview mt-3 text-sm text-gray-600"></div>
            </div>

            <!-- Attendance IN -->
            <div class="upload-box border-2 border-dashed border-primary rounded-2xl p-6 text-center"
                data-input="attendance_in" data-type="attendance">
                <img src="{{ asset('/images/upload.png') }}" class="mx-auto w-10 mb-2" />
                <p class="text-primary font-semibold">Upload Attendance List - IN</p>
                <input type="file" name="attendance_in" class="hidden proof_files" id="attendance_in">
                <div class="preview mt-3 text-sm text-gray-600"></div>
            </div>

            <!-- Attendance OUT -->
            <div class="upload-box border-2 border-dashed border-primary rounded-2xl p-6 text-center"
                data-input="attendance_out" data-type="attendance">
                <img src="{{ asset('/images/upload.png') }}" class="mx-auto w-10 mb-2" />
                <p class="text-primary font-semibold">Upload Attendance List - OUT</p>
                <input type="file" name="attendance_out" class="hidden proof_files" id="attendance_out">
                <div class="preview mt-3 text-sm text-gray-600"></div>
            </div>
        </div>

        <!-- SUBMIT BUTTON -->
        <div class="flex justify-center mt-12 mb-10">
            <button type="submit"
                class="px-8 bg-gradient-to-r from-primary to-pink-600 text-white font-semibold py-2 rounded-full hover:opacity-90 transition">
                Submit Reports
            </button>
        </div>
    </form>
</x-layouts.app>

<script src="{{ asset('admin/js/report.js') }}"></script>
