@props(['route' => '', 'icon' => 'fa fa-home', 'name' => 'Dashboard', 'trigger' => false])
<li class="w-full mt-1">
    @if ($route)
        <a href="{{ $route ? route($route) : '#' }}"
            class="flex w-full text-primary hover:text-white items-center gap-2 px-2 py-2 text-[14px] font-medium rounded-3xl transition hover:bg-primary @if ($route && request()->routeIs($route)) bg-primary text-white not-only:@endif">
            <i class="fa {{ $icon }} w-5"></i>
            <span>{{ $name }}</span>

            @if ($trigger)
                <i class="fa fa-chevron-down w-5"></i>
            @endif
        </a>
    @else
        <button type="submit"
            class="flex w-full text-primary hover:text-white items-center gap-2 px-2 py-2 text-[14px] font-medium rounded-3xl transition hover:bg-primary @if ($route && request()->routeIs($route)) bg-primary @endif ">
            <i class="fa {{ $icon }} w-5"></i>
            <span class="flex-1 text-left">{{ $name }}</span>

            @if ($trigger)
                <i class="fa fa-chevron-down w-5"></i>
            @endif
        </button>
    @endif
</li>
