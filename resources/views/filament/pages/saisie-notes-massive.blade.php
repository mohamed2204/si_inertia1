<x-filament::page>
    {{ $this->form }}

    <x-filament::button color="warning" class="mt-4" wire:click="importerExcel">
        Importer depuis Excel
    </x-filament::button>

    {{-- <x-filament::button class="mt-4" wire:click="chargerEleves">
        Charger les élèves
    </x-filament::button> --}}

    {{-- @if (count($notes))
        <div class="mt-6 space-y-2">
            @foreach ($notes as $eleveId => $note)
                <div class="flex items-center gap-4">
                    <div class="w-64">
                        {{ \App\Models\Eleve::find($eleveId)->nom }}
                    </div>

                    <x-filament::input type="number" step="0.25" min="0" max="20"
                        wire:model.defer="notes.{{ $eleveId }}" class="w-24" />
                </div>
            @endforeach
        </div>

        <x-filament::button color="success" class="mt-6" wire:click="enregistrer">
            Enregistrer toutes les notes
        </x-filament::button>
    @endif --}}
</x-filament::page>
