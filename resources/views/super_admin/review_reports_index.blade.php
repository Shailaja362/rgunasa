<x-layouts.app>
    <!-- Header -->
    <div class="bg-[#F5E8F5] w-full h-[90px] rounded-full shadow-sm px-8 py-3 flex flex-col justify-center">
        <h3 class="font-semibold text-primary">Review Report</h3>
        <p class="text-sm text-gray-700">Submit comprehensive reports for completed events</p>
    </div>
    <!-- Overview Cards -->
    <section class="p-3">
        <!-- Filters Section -->
        <h1 class="text-primary mt-3 font-semibold">Review Reports</h1>
        <div class="bg-white rounded-2xl shadow py-8 px-7 mt-3">
            @if($reports->isNotEmpty())
            @foreach ($reports as $report)
            <div class="shadow p-5 rounded-2xl mt-5">

               <div class="flex items-center justify-between">
                   <h1 class="font-bold">{{ $report->get_event->title ?? '' }}</h1>
                    <a href="{{ route('reports_view_pdf', $report->id) }}" target="_blank"
                        class="text-center bg-[#F5F7F9] font-medium py-1 rounded-full px-4">
                        <i class="fa fa-eye" aria-hidden="true"></i> View Pdf
                    </a>
               </div>
                   <p>{{ $report->creator->name ?? '' }}</p>
                    <div class="flex items-center justify-between">
                   <p class="text-xs mt-2"><i class="fa fa-calendar text-primary "></i> Events : {{ \Carbon\Carbon::parse($report->get_event->event_date)->format('d/m/Y') }}    <i class="fa fa-calendar text-primary"></i> Submitted :  {{ \Carbon\Carbon::parse($report->created_at)->format('d/m/Y') }}</p>
                    <a href="{{ route('assign_grade_entry', ['event_id' => $report->event_id]) }}"  data-event_id="{{ $report->event_id }}"
                         class="inline-block bg-[#6C2DC7] text-white font-medium py-1 rounded-full text-end px-4">
                         <i class="fa fa-plus" aria-hidden="true"></i> Add Grade
                       </a>
                    </div>
             </div>
             @endforeach
             @else
             <p class="text-center">No reports available</p>
             @endif
            </div>
    </section>
</x-layouts.app>
