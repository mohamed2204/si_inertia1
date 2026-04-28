<x-filament-panels::page>

    <x-filament-actions::modals/>

    <form wire:submit.prevent="save">
        {{ $this->form }}
    </form>

    <x-filament::modal id="custom-confirm-modal" max-width="md">
        <x-slot name="heading">
            Confirmation Requise
        </x-slot>

        <x-slot name="description">
            Voulez-vous vraiment importer ces notes ?
        </x-slot>

        <div>
            <p>Les notes déjà importées seront remplacéés par les nouvelles.</p>
        </div>

        <x-slot name="footer">

            <x-filament::button color="gray" wire:click="$dispatch('close-modal', { id: 'custom-confirm-modal' })"
                                wire:loading.attr="disabled" class="mr-3">
                Annuler
            </x-filament::button>

            <x-filament::button color="danger" wire:click="confirmImport" x-on:click="close()"
                                wire:loading.attr="disabled">
                Confirmer l'importation
            </x-filament::button>
        </x-slot>
    </x-filament::modal>

    <x-filament::modal id="MappingModal" max-width="md">
        <x-slot name="heading">
            Confirmation mapping
        </x-slot>

        <x-slot name="description">
            Voulez-vous vraiment importer ces notes ?
        </x-slot>

        <div>
            <p>Les notes déjà importées seront remplacéés par les nouvelles.</p>
        </div>

        <x-slot name="footer">

            <x-filament::button color="gray" wire:click="$dispatch('close-modal', { id: 'custom-confirm-modal' })"
                                wire:loading.attr="disabled" class="mr-3">
                Annuler
            </x-filament::button>

            <x-filament::button color="danger" wire:click="confirmImport" x-on:click="close()"
                                wire:loading.attr="disabled">
                Confirmer l'importation
            </x-filament::button>
        </x-slot>
    </x-filament::modal>

    {{-- 1. Affichage des erreurs de validation --}}
  {{--  @if (count($validationErrors) > 0)
        <div class="p-4 mt-4 border rounded-lg bg-danger-50 border-danger-200">
            <h3 class="flex items-center font-bold text-danger-800">
                <x-heroicon-m-x-circle class="w-5 h-5 mr-2"/>
                Erreurs de validation du fichier
            </h3>
            <ul class="mt-2 list-disc list-inside text-danger-700">
                @foreach ($validationErrors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif--}}

    {{-- 2. Barre de progression de l'importation --}}
    @if ($isImporting)
        <div class="p-6 mt-8 bg-white border shadow-md dark:bg-gray-800 rounded-xl">
            {{--
            Le secret est ici : wire:init appelle la méthode dès que ce bloc HTML apparaît.
            Ensuite, à la fin de chaque exécution de importNextChunk,
            Livewire rafraîchit le composant et relance l'appel tant que $isImporting est vrai.
            --}}
            <div wire:init="importNextChunk">
                <div class="flex items-center text-primary-600 animate-pulse">
                    <x-heroicon-m-arrow-path class="w-5 h-5 mr-2 animate-spin"/>
                    Traitement en cours...
                </div>
            </div>
        </div>
        <div class="p-6 mt-8 bg-white border shadow-md dark:bg-gray-800 rounded-xl">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                    Importation : {{ $processedRows }} / {{ $totalRows }}
                </span>
                <span class="text-sm font-bold text-primary-600">
                    {{ $this->progress }}%
                </span>
            </div>
            <div class="w-full h-4 overflow-hidden bg-gray-200 rounded-full dark:bg-gray-700">
                <div class="h-4 transition-all duration-500 bg-primary-600" style="width: {{ $this->progress }}%">
                </div>
            </div>
            @if ($isImporting && $processedRows < $totalRows)
                <div wire:init="importNextChunk" wire:key="chunk-{{ $processedRows }}">
                    <div class="flex items-center text-primary-600 animate-pulse">
                        <x-heroicon-m-arrow-path class="w-5 h-5 mr-2 animate-spin"/>
                        Traitement de la ligne {{ $processedRows }}...
                    </div>
                </div>
            @endif
            {{-- Affichage en temps réel dans la vue Blade --}}
            <div class="grid grid-cols-2 gap-4 mt-4">
                <div class="p-3 text-center text-green-800 bg-green-100 rounded-lg">
                    <span class="text-xl font-bold">{{ $createdCount }}</span><br>
                    Nouveaux
                </div>
                <div class="p-3 text-center text-blue-800 bg-blue-100 rounded-lg">
                    <span class="text-xl font-bold">{{ $updatedCount }}</span><br>
                    Mis à jour
                </div>
            </div>
        </div>
    @endif

</x-filament-panels::page>

