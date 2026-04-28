<x-filament::page>
    <form wire:submit.prevent="submit" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit" color="primary">
            {{ $record ? 'Mettre à jour' : 'Créer' }}
        </x-filament::button>
    </form>
{{--    <pre>Contenu de data : {{ var_export($data, true) }}</pre>--}}
</x-filament::page>
