@if ($info)
<x-filament::card>
    <div class="p-3 rounded inline-block">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                     bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-700/20 dark:text-{{ $color }}-200">
            {{ $info }}
        </span>
    </div>
</x-filament::card>
@endif
