<x-filament::page>

    <h2 class="text-lg font-medium">
        Import des élèves
    </h2>

    {{-- FORMULAIRE --}}
    <div class="mt-4">
        {{ $this->form }}
    </div>

    {{-- BOUTONS --}}
    <div class="flex gap-3 mt-6">
        <x-filament::button wire:click="preview" icon="heroicon-o-eye">
            Aperçu
        </x-filament::button>

        <x-filament::button wire:click="confirmImport" color="success" icon="heroicon-o-check" :disabled="count($errors) > 0 || empty($rows)">
            Confirmer l’import
        </x-filament::button>
    </div>

    {{-- ERREURS --}}
    @if (!empty($errors))
        <div class="mt-6 space-y-1 text-sm text-danger-600">
            @foreach ($errors as $error)
                <div>• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- APERÇU --}}
    @if (!empty($rows))
        <div class="mt-8">
            {{ $this->table ?? '' }}
        </div>
    @endif

</x-filament::page>
