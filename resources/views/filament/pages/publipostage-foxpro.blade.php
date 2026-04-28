<x-filament-panels::page>
    <form wire:submit.prevent="submit" class="space-y-6">
        {{ $this->form }}

       {{-- <x-filament::button type="submit">
            Générer le publipostage Word
        </x-filament::button>--}}
        <x-filament::button
            type="submit"
            wire:loading.attr="disabled"
        >
            Générer
        </x-filament::button>
    </form>
</x-filament-panels::page>
