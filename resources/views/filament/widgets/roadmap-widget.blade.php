<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Frise chronologique (Timeline)</x-slot>

        <div class="space-y-8">
            {{--  --}}
            {{-- @foreach ($this->getPromotionsData() as $promo)
                <div>
                    <h3 class="mb-3 text-sm font-bold text-gray-600 dark:text-gray-400">{{ $promo['nom'] }}</h3>

                    <div class="relative flex items-center w-full">
                        @foreach ($promo['phases'] as $phase)
                            <div class="relative flex-1 group">
                                <div @class([
                                    'h-4 border-r-2 border-white dark:border-gray-900 transition-all',
                                    'opacity-40' => $phase['status'] === 'completed',
                                    'ring-4 ring-primary-500 shadow-lg z-10 scale-y-125' =>
                                        $phase['status'] === 'current',
                                    'bg-gray-200' => $phase['status'] === 'pending' && !$phase['couleur'],
                                ]) style="background-color: {{ $phase['couleur'] }}">
                                </div>

                                <div class="mt-2 text-[10px] text-center truncate px-1">
                                    <span class="font-semibold">{{ $phase['label'] }}</span><br>
                                    <span class="text-gray-500">{{ $phase['debut'] }} - {{ $phase['fin'] }}</span>
                                </div>

                                @if ($phase['status'] === 'current')
                                    <div class="absolute -translate-x-1/2 -top-6 left-1/2">
                                        <span class="relative flex w-3 h-3">
                                            <span
                                                class="absolute inline-flex w-full h-full rounded-full opacity-75 animate-ping bg-primary-400"></span>
                                            <span
                                                class="relative inline-flex w-3 h-3 rounded-full bg-primary-500"></span>
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach --}}
        </div>

        {{-- zone de recherche  --}}
        {{-- <div class="flex items-center gap-2 p-2 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-900 dark:border-gray-700">
            <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass" class="flex-1">
                <x-filament::input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Rechercher une promotion..."
                />
            </x-filament::input.wrapper>
        </div> --}}


        <div class="grid grid-cols-1 gap-4">
            @forelse($this->getPromotionsData as $promo)
                <x-filament::section collapsible :collapsed="true" compact>
                    <x-slot name="heading">
                        <div class="flex items-center gap-4">
                            <span class="font-bold text-gray-800 dark:text-white">{{ $promo['nom'] }}</span>
                            <x-filament::badge color="primary"
                                size="xs">{{ $promo['progression'] }}%</x-filament::badge>
                        </div>
                    </x-slot>
                    @foreach ($promo['phases'] as $phase)
                        <div class="relative pb-6 pl-4 last:pb-0">
                            <div class="absolute left-[7px] top-2 bottom-0 w-[2px] bg-gray-200 dark:bg-gray-700"></div>

                            <div class="flex items-start">

                                <div class="relative z-10 flex items-center justify-center">
                                    <div @class([
                                        'h-4 w-4 rounded-full border-2 border-white dark:border-gray-900 shadow-sm',
                                        'animate-pulse' => $phase['status'] === 'current',
                                    ])
                                        style="background-color: {{ $phase['couleur'] }}; margin-left: -9px; margin-top: 4px;">
                                    </div>

                                    <div style="width: 20px; height: 1px; background-color: #d1d5db; margin-top: 4px;">
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0" style="padding-left: 8px;">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-bold text-gray-900 dark:text-white"
                                                style="margin: 0;">
                                                {{ $phase['label'] }}
                                            </p>
                                            <p class="text-[10px] text-gray-500" style="margin: 0;">
                                                {{ $phase['debut']->format('d/m/Y') }} -
                                                {{ $phase['fin']->format('d/m/Y') }}
                                            </p>
                                        </div>

                                        {{-- @if ($phase['status'] === 'current')
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                                                En cours
                                            </span>
                                        @endif --}}
                                        @if ($phase['status'] === 'current')
                                            <x-filament::badge size="xs" color="primary">En
                                                cours</x-filament::badge>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    {{-- <div class="relative mt-4 ml-2">
                        <div class="absolute left-[7px] top-2 bottom-2 w-0.5 bg-gray-200 dark:bg-gray-700"></div>
                        @foreach ($promo['phases'] as $phase)
                            <div class="relative pb-6 pl-8 last:pb-0">
                                <div @class([
                                    'absolute left-0 top-1.5 h-4 w-4 rounded-full border-2 border-white dark:border-gray-900 z-10',
                                    'ring-4 ring-primary-500/30 animate-pulse' =>
                                        $phase['status'] === 'current',
                                ]) style="background-color: {{ $phase['couleur'] }}">
                                </div>

                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="ml-5 text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $phase['label'] }}
                                        </p>
                                        <p class="text-[10px] text-gray-500 ml-5">{{ $phase['debut']->format('d/m/Y') }}
                                            {{ $phase['fin']->format('d/m/Y') }}</p>
                                    </div>
                                    @if ($phase['status'] === 'current')
                                        <x-filament::badge size="xs" color="primary">En cours</x-filament::badge>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div> --}}
                </x-filament::section>
            @empty
                <div class="p-8 italic text-center text-gray-500">
                    {{-- Aucune promotion trouvée pour "{{ $search }}" --}}
                    Aucune promotion trouvée.
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
