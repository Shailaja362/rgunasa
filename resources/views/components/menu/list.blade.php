<div x-data="{ open: true }" class="w-full border-y-1 border-gray-200/30 py-2">
    <div class="w-full" @click="open = !open">
        {{ $trigger }}
    </div>

    <div x-show="open" x-transition class="px-3">
        {{ $menus }}
    </div>
</div>
