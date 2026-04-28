{{--<x-filament::page>--}}
{{--    <form wire:submit.prevent="submit">--}}
{{--        {{ $this->form }}--}}

{{--        <x-filament::button type="submit" class="mt-6">--}}
{{--            Enregistrer les phases--}}
{{--        </x-filament::button>--}}
{{--    </form>--}}
{{--</x-filament::page>--}}
<x-filament-panels::page>
    <form wire:submit="submit">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Enregistrer les phases
            </x-filament::button>

            <x-filament::button color="gray" tag="a" :href="static::$resource::getUrl('index')">
                Annuler
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
