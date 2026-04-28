<x-filament::button
    type="submit"
    size="md"
    wire:loading.attr="disabled"
    wire:target="startImport"
    icon="heroicon-m-check"
    x-bind:disabled="Object.keys($wire.importErrors).length > 0"
>
    Lancer l'importation
</x-filament::button>
<span class="text-xs text-gray-400">
    DEBUG : Nb erreurs = <span x-text="Object.keys($wire.importErrors).length"></span>
</span>
