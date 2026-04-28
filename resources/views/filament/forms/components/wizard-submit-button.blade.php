@php
    // On récupère les données depuis le composant parent
    $data = $this->importData;
    $errors = $this->validationErrors;
    $isValid = $this->isValid;
    $validationErrors = $this->validationErrors;
    $errorCount = $this->errorCount;
    $importErrors = $this->importErrors;
@endphp

{{-- // x-bind:disabled="Object.keys($wire.importErrors).length > 0" --}}

<x-filament::button
    :color="$errorCount > 0 ? 'gray' : 'primary'"
    :disabled="$errorCount > 0"
    wire:key="btn-import"
    wire:loading.attr="disabled"
                    {{-- -   wire:click="confirmImport"  --}}
     wire:click="$dispatch('open-modal', { id: 'custom-confirm-modal' })"
     icon="heroicon-m-check"
     size="sm">
    @if ($errorCount > 0)
        Lancer l'importation (corrigez les erreurs)
    @else
        Lancer l'importation
    @endif
</x-filament::button>

{{-- -
<x-filament::button
    wire:click="$dispatch('open-modal', { id: 'custom-confirm-modal' })">
    Ouvrir la modale
</x-filament::button>
--}}
@if ($errorCount > 0)
    <div class="mt-8">
        <x-filament::badge color="danger" icon="heroicon-m-exclamation-triangle">
            {{ $errorCount }} erreur(s) à corriger
        </x-filament::badge>
    </div>
@endif
