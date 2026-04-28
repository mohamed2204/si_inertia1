<div class="flex flex-wrap gap-2 mt-4">
    @foreach ($specialites as $nom => $couleur)
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
            style="background-color: {{ $couleur }}20; border-color: {{ $couleur }}; color: {{ $couleur }}">
            <svg class="-ml-0.5 mr-1.5 h-2 w-2" style="fill: {{ $couleur }}" viewBox="0 0 8 8">
                <circle cx="4" cy="4" r="3" />
            </svg>
            {{ $nom }}
        </span>
    @endforeach
</div>

{{-- <div
    class="flex flex-wrap gap-4 p-4 mt-4 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 rounded-xl dark:border-gray-700">
    <div class="w-full mb-2 text-sm font-medium text-gray-500">Légende des spécialités :</div>
    @foreach ($specialites as $nom => $couleur)
        <div class="flex items-center gap-2">
            <span class="w-4 h-4 rounded-full" style="background-color: {{ $couleur }}"></span>
            <span class="text-sm text-gray-600 dark:text-gray-300">{{ $nom }}</span>
        </div>
    @endforeach
</div> --}}
