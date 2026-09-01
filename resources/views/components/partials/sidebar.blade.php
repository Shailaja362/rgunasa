<aside class="fixed top-0 left-0 h-full w-64 mx-3 overflow-y-auto border-r-1 border-zinc-300 bg-[#FCF7FC]">
    <!-- Brand Logo -->
    <div class="p-3">
        <x-app-logo />
    </div>
    {{-- PROFILE SEGMENT IN SIDEBAR --}}
    <div class="space-y-2">
        @php
            $user = session()->get('student') ?? (session()->get('admin') ?? session()->get('super_admin'));
            $profile_pict = $user->profile_pic ?? null;
            if (!empty(session()->get('student'))) {
                $student = session()->get('student');
                $name = $student->name ?? '';
                $id = $student->register_number ?? '';
                $dept_name = $student->get_department->name ?? '';
                $route = route('student.logout');
            } elseif (!empty(session()->get('admin'))) {
                $admin = session()->get('admin');
                $name = $admin->name;
                $id = $admin->emp_code;
                $dept_name = $admin->get_department->name ?? '';
                $desig_name = $admin->get_designation->name ?? '';
                $route = route('admin.logout');
            } elseif (!empty(session()->get('super_admin'))) {
                $admin = session()->get('super_admin');
                $name = $admin->name;
                $id = $admin->emp_code;
                $dept_name = $admin->get_department->name ?? '';
                $desig_name = $admin->get_designation->name ?? '';
                $route = route('admin.logout');
            }
        @endphp

        @if (!empty($profile_pict))
            <img src="{{ asset('storage/' . $profile_pict) }}" alt="Profile Picture"
                class="mx-auto rounded-full w-2/5 h-25">
        @else
            <img src="{{ asset('/images/user_dummy.png') }}" alt="Default Picture" class="mx-auto w-3/5">
        @endif

        <div class="text-center font-medium text-md text-black">
            Welcome Back
        </div>
        <div class="text-center font-medium text-md text-primary">
            {{ $name }}
        </div>
        <div class="text-center font-sm text-sm text-primary">
            {{ $dept_name }}
        </div>
        @if (!empty(session()->get('admin')) || !empty(session()->get('super_admin')))
            <div class="text-center font-sm text-sm">
                {{ $desig_name }}
            </div>
        @endif
        <div class="text-center font-medium text-md text-primary">
            ID - {{ $id }}
        </div>
    </div>
    <ul class="">
        @if (!empty(session()->get('student')))
            <x-menu.list>
                <x-slot:trigger>
                    <x-menu.item trigger name="Curriculum" icon="fa-book" />
                </x-slot:trigger>
                <x-slot:menus>
                    <x-menu.item name="Dashboard" icon="fa-home" route="student_dashboard" />
                    <x-menu.item name="Register for Events" icon="fa-calendar" route="register_events" />
                    <x-menu.item name="My Registration" icon="fa-check-circle" route="my_register_events" />
                    <x-menu.item name="Certificates" icon="fa-trophy" route="certificates" />
                </x-slot:menus>
            </x-menu.list>
        @endif
        @if (!empty(session()->get('admin')))
            <x-menu.list>
                <x-slot:trigger>
                </x-slot:trigger>
                <x-slot:menus>
                    <x-menu.item name="Home" icon="fa-home" route="home" />
                </x-slot:menus>
            </x-menu.list>
            <x-menu.list>
                <x-slot:trigger>
                    <x-menu.item trigger name="Configuration" icon="fa-conf" />
                </x-slot:trigger>
                <x-slot:menus>
                    <x-menu.item name="Department" icon="fas fa-building" route="department_list" />
                    <x-menu.item name="Programme" icon="fas fa-book" route="programme_list" />
                    <x-menu.item name="Faculty" icon="fas fa-chalkboard-teacher" route="faculty_list" />
                    <x-menu.item name="Student" icon="fas fa-user-graduate" route="student_list" />
                    <x-menu.item name="Batch" icon="fas fa-layer-group" route="batch_list" />
                    <x-menu.item name="Club" icon="fas fa-users" route="club_list" />
                    <x-menu.item name="Credit Point Assign" icon="fa-trophy" route="credit_point_assign" />
                </x-slot:menus>
            </x-menu.list>
            <x-menu.list>
                <x-slot:trigger>
                    <x-menu.item trigger name="Event Management" icon="fa-conf" />
                </x-slot:trigger>
                <x-slot:menus>
                    <x-menu.item name="Create Event" icon="fas fa-pencil-alt" route="event_list" />
                    <x-menu.item name="Registered Students" icon="fa-graduation-cap" route="registered_report_index" />
                    <x-menu.item name="Student Attendance" icon="fa-graduation-cap" route="student_attendance" />
                    <x-menu.item name="Assign Grades" icon="fa-star" route="assign_grades" />
                    <x-menu.item name="Reports" icon="fa-book" route="reports" />
                </x-slot:menus>
            </x-menu.list>
        @endif
        @if (!empty(session()->get('super_admin')))
            <x-menu.list>
                <x-slot:trigger>
                </x-slot:trigger>
                <x-slot:menus>
                    <x-menu.item name="Home" icon="fa-home" route="super_admin_home" />
                </x-slot:menus>
            </x-menu.list>
            <x-menu.list>
                <x-slot:trigger>
                    <x-menu.item trigger name="Configuration" icon="fa-conf" />
                </x-slot:trigger>
                <x-slot:menus>
                    <x-menu.item name="Department" icon="fas fa-building" route="department_list" />
                    <x-menu.item name="Programme" icon="fas fa-book" route="programme_list" />
                    <x-menu.item name="Faculty" icon="fas fa-chalkboard-teacher" route="faculty_list" />
                    <x-menu.item name="Student" icon="fas fa-user-graduate" route="student_list" />
                    <x-menu.item name="Batch" icon="fas fa-layer-group" route="batch_list" />
                    <x-menu.item name="Club" icon="fas fa-users" route="club_list" />
                    <x-menu.item name="Admin" icon="fas fa-chalkboard-teacher" route="admin_list" />
                    <x-menu.item name="Credit Point Assign" icon="fa-trophy" route="credit_point_assign" />
                </x-slot:menus>
            </x-menu.list>
            <x-menu.list>
                <x-slot:trigger>
                    <x-menu.item trigger name="Event Management" icon="fa-conf" />
                </x-slot:trigger>
                <x-slot:menus>
                    <x-menu.item name="Events" icon="fa-calendar-minus" route="events" />
                    <x-menu.item name="Create Event" icon="fa-pencil-square" route="event_list" />
                    <x-menu.item name="Registered Students" icon="fa-graduation-cap" route="registered_report_index" />
                    <x-menu.item name="Assign Tasks" icon="fa-check-circle" route="assign_tasks" />
                    <x-menu.item name="Student Attendance" icon="fa-graduation-cap" route="student_attendance" />
                    <x-menu.item name="Assign Grades" icon="fa-star" route="assign_grades" />
                    <x-menu.item name="Reports" icon="fa-book" route="reports" />
                    <x-menu.item name="Review Reports" icon="fa-check-circle" route="review_reports" />
                    <x-menu.item name="Student Credits" icon="fa-trophy" route="student_credits" />
                </x-slot:menus>
            </x-menu.list>
        @endif

        <x-menu.item name="Privacy Policy" icon="fa-book-open" route="privacy_policy" />
        <x-menu.item name="Terms and Conditions" icon="fa-file-contract " route="terms_and_conditions" />
        @if (!empty(session()->get('student')))
            {{-- <x-menu.item name="Non-Curriculum" icon="fa-graduation-cap" /> --}}
        @endif
    </ul>
    {{-- MENU SEGMENT IN SIDEBAR -ENDS --}}
    <div class="flex gap-3 mt-16 bg-[#F7E9F7] rounded-full">
        @if (!empty($profile_pict))
            <img src="{{ asset('storage/' . $profile_pict) }}" alt="Profile Picture" class="rounded-full w-1/5 h-15">
        @else
            <img src="{{ asset('/images/user_dummy.png') }}" alt="Default Picture" class="w-1/5">
        @endif
        <div class="my-auto space-y-1">
            <form method="POST" action="{{ $route }}">
                @csrf
                <button type="submit" class="text-sm font-medium hover:underline">
                    Logout
                </button>
            </form>
            <p class="text-sm font-medium text-black">ID -
                {{ $id }}
            </p>
        </div>
    </div>
</aside>
