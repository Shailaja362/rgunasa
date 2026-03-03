<x-layouts.app>
    <div class="bg-[#F5E8F5] w-full rounded-full shadow-sm px-8 py-3">
        <h3 class="font-semibold text-primary">Assign Tasks</h3>
    </div>
    <a href="{{ route('assign_tasks') }}"><i class="fa-solid fa-arrow-left"></i></a>

    <section class="p-3">
        <h3 class="font-semibold text-primary mt-8">Assign Task to Admin</h3>
        <form id="taskForm" method="POST" enctype="multipart/form-data" class="space-y-4 mt-8">
            @csrf
            @if (!empty($edit_task))
                <input type="hidden" name="task_id" value="{{ $edit_task->id }}">
            @endif
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Select Admin -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium">Select Admin<span class="text-red-600">*</span></label>
                    <select name="admin_id" id="admin_id"
                        class="choice-select bg-[#D9D9D9] w-full rounded-full px-4 py-3 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
                        <option value="">Select Admin</option>
                        @foreach ($admins as $admin)
                            <option value="{{ $admin->id }}"
                                {{ !empty($edit_task) && $edit_task->admin_id == $admin->id ? 'selected' : '' }}>
                                {{ $admin->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Task Title -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium">Task Title<span class="text-red-500">*</span></label>
                    <input type="text" name="task_title" id="task_title" placeholder="Enter Task Title"
                        value="{{ !empty($edit_task) ? $edit_task->title : '' }}"
                        class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
                </div>

                <!-- Description -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium">Description<span class="text-red-600">*</span></label>
                    <textarea name="description" id="description"
                        class="bg-[#D9D9D9] w-full p-3 border border-gray-300 rounded-2xl focus:outline-none focus:ring focus:ring-primary/40"
                        rows="4" placeholder="Describe the task details">{{ !empty($edit_task) ? $edit_task->description : '' }}</textarea>
                </div>

                <!-- Priority -->
                <div>
                    <label class="block text-sm font-medium">Priority Level<span class="text-red-500">*</span></label>
                    <select name="priority" id="priority"
                        class="bg-[#D9D9D9] w-full rounded-full py-3 px-3 focus:outline-none focus:ring focus:ring-primary/40">
                        <option value="">Select Priority Level</option>
                        <option value="low"
                            {{ !empty($edit_task) && $edit_task->priority == 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium"
                            {{ !empty($edit_task) && $edit_task->priority == 'medium' ? 'selected' : '' }}>Medium
                        </option>
                        <option value="high"
                            {{ !empty($edit_task) && $edit_task->priority == 'high' ? 'selected' : '' }}>High</option>
                    </select>
                </div>

                <!-- Deadline -->
                <div>
                    <label class="block text-sm font-medium">Deadline Date<span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="deadline_date" id="deadline_date"
                        value="{{ !empty($edit_task) ? \Carbon\Carbon::parse($edit_task->deadline_date)->format('Y-m-d\TH:i') : '' }}"
                        class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
                </div>

                <!-- Image Upload area (Blade) -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium mb-2">Upload Images</label>

                    <!-- Drop area -->
                    <div id="dropArea"
                        class="border-2 border-dashed border-[#E54590] rounded-2xl p-6 text-center cursor-pointer"
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
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-center mt-10">
                <button type="submit"
                    class="px-10 bg-gradient-to-r from-primary to-pink-600 text-white font-semibold py-1 rounded-full hover:opacity-90 transition">
                    Assign Task
                </button>
            </div>
        </form>
    </section>

</x-layouts.app>
<script src="{{ asset('admin/js/tasks.js') }}?v={{ time() }}"></script>
