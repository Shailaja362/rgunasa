<x-layouts.app>

    <!-- Header -->
    <div class="bg-[#F5E8F5] w-full h-[90px] rounded-full shadow-sm px-8 py-3 flex flex-col justify-center">
        <h3 class="font-semibold text-primary">Student Attendance Management</h3>
        <p class="text-sm text-gray-700">Mark entry & exit and download attendance</p>
    </div>

    <!-- Overview Cards -->
    <section class="p-3 mt-4">
        <!-- Attendance Table -->
        <div class="mt-4 bg-white rounded-2xl shadow overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-primary text-white text-sm uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">S.No</th>
                        <th class="px-4 py-3">Event Name</th>
                        <th class="px-4 py-3">Event Date</th>
                        <th class="px-4 py-3">Contact Person</th>
                        <th class="px-4 py-3">Contact Email</th>
                        <th class="px-4 py-3">Club Name</th>
                        <th class="px-4 py-3 text-center">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($events as  $event)
                        <tr class="border-t">
                            <td class="px-4 py-3">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 font-medium">{{ $event->title ?? '' }}</td>
                            <td class="px-4 py-3">{{ $event->event_date ?? '' }}</td>
                            <td class="px-4 py-3">{{ $event->contact_person ?? '' }}</td>
                            <td class="px-4 py-3">{{ $event->contact_email ?? '' }}</td>
                            <td class="px-4 py-3">{{ $event->get_club?->name ?? '' }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex gap-2 justify-center">
                                    <a href="{{ route('student_attendance_entry', ['event_id' => $event->id]) }}" data-event_id="{{ $event->id }}" class="bg-[#DA70D6] text-white px-3 py-1 rounded-full text-xs">
                                        Attendance Entry
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-6 text-gray-500">
                                No records found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

</x-layouts.app>
<script>
    const attendanceMarkUrl = "{{ route('attendance.mark') }}";
</script>
<script src="{{ asset('admin/js/student_attendance.js') }}?v={{ time() }}"></script>
