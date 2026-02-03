   @if (!empty(session()->get('super_admin')))
             @php
              $url =  route('super_admin_home') ;
              $title ='Super Admin Portal';
             @endphp
    @elseif (!empty(session()->get('admin')))
             @php
              $url = route('home');
              $title ='Admin Portal';
             @endphp
    @elseif (!empty(session()->get('student')))
             @php
              $url = route('student_dashboard');
              $title ='Student Portal';
             @endphp

    @endif
   <a href="{{ $url }}" class="space-y-1">
       <img src="{{ asset('/images/rtc_logo.png') }}" alt="RTC Logo" class="h-10 w-50 object-contain">
       <div class="text-4xl font-bold text-primary">NASA</div>
       <div class="text-xs font-bold">Non Academic Student Application</div>
       <div class="text-lg font-medium">
          {{ $title }}
       </div>
   </a>
