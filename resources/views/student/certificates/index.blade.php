<x-layouts.app>
    <div class="bg-[#F2E8F5] rounded-full px-5 py-3 flex justify-between items-center">
            <h3 class="font-semibold text-primary">Certificates</h3>
    </div>
    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($completedEvents as $event)
            <div class="bg-[#F2E8F5] rounded-2xl text-center shadow-md">
                <div class="relative bg-white border-4 border-primary rounded-2xl shadow-md p-6 text-center">
                    <div class="flex justify-center">
                         <img src="{{ asset('/images/badge.jpeg') }}" class="mx-auto w-14 mb-3" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-700">{{ $event->student->name }}</h3>
                        <h3 class="text-sm font-bold text-primary mt-2">{{ $event->event->title }}</h3>
                        <p class="text-gray-600 mt-2 text-sm">This is to certify that</p>
                        <p class="text-gray-600 text-sm">has successfully completed the event</p>
                        <p class="font-medium text-gray-700 mt-1 italic"></p>
                    </div>
                    <div class="absolute inset-0 rounded-2xl border border-[#9D55EC]/20 pointer-events-none"></div>
                    <a href="{{ route('certificate_download', ['event_id' => $event->event->id ,'student_id' => $event->student->id , 'event_schedule_id' => $event->event_schedule_id ]) }}"
                       class="bg-primary text-white px-5 py-2 mt-4 rounded-full text-sm inline-flex items-center justify-center gap-2">
                        Download Certificate
                      <i class="fa fa-download" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</x-layouts.app>


