<x-filament-panels::page>
   {{-- <form wire:submit.prevent="save">
        {{ $this->form }}
    </form>--}}
0
    {{-- 1. Affichage des erreurs de validation --}}
   {{-- @if (count($validationErrors) > 0)
        <div class="mt-4 p-4 bg-danger-50 border border-danger-200 rounded-lg">
            <h3 class="text-danger-800 font-bold flex items-center">
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
        <div class="mt-8 p-6 bg-white dark:bg-gray-800 border rounded-xl shadow-md">
            {{--
           Le secret est ici : wire:init appelle la méthode dès que ce bloc HTML apparaît.
           Ensuite, à la fin de chaque exécution de importNextChunk,
           Livewire rafraîchit le composant et relance l'appel tant que $isImporting est vrai.
        --}}
            <div wire:init="importNextChunk">
                <div class="flex items-center text-primary-600 animate-pulse">
                    <x-heroicon-m-arrow-path class="w-5 h-5 mr-2 animate-spin" />
                    Traitement en cours...
                </div>
            </div>
        </div>
        <div class="mt-8 p-6 bg-white dark:bg-gray-800 border rounded-xl shadow-md">
            <div class="flex justify-between items-center mb-4">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                    Importation : {{ $processedRows }} / {{ $totalRows }}
                </span>
                <span class="text-sm font-bold text-primary-600">
                    {{ $this->progress }}%
                </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                <div class="bg-primary-600 h-4 transition-all duration-500" style="width: {{ $this->progress }}%"></div>
            </div>
            @if ($isImporting && $processedRows < $totalRows)
                <div wire:init="importNextChunk" wire:key="chunk-{{ $processedRows }}">
                    <div class="flex items-center text-primary-600 animate-pulse">
                        <x-heroicon-m-arrow-path class="w-5 h-5 mr-2 animate-spin" />
                        Traitement de la ligne {{ $processedRows }}...
                    </div>
                </div>
            @endif
            {{-- Affichage en temps réel dans la vue Blade --}}
            <div class="grid grid-cols-2 gap-4 mt-4">
                <div class="p-3 bg-green-100 text-green-800 rounded-lg text-center">
                    <span class="text-xl font-bold">{{ $createdCount }}</span><br>
                    Nouveaux
                </div>
                <div class="p-3 bg-blue-100 text-blue-800 rounded-lg text-center">
                    <span class="text-xl font-bold">{{ $updatedCount }}</span><br>
                    Mis à jour
                </div>
            </div>
        </div>
    @endif
    {{-- Dump and die --}}
    {{-- {{ dd($importData) }} --}}
    {{-- 3. Aperçu des données --}}
   {{-- @if ($isValid && count($importData) > 0 && !$isImporting)
        <div class="mt-6">
            <h3 class="text-lg font-bold mb-4 italic text-gray-600">Aperçu du fichier</h3>
            <div class="overflow-x-auto border rounded-lg shadow-sm">
                <table class="w-full text-left bg-white dark:bg-gray-800 text-sm">
                    <thead>
                    <tr class="bg-gray-100 dark:bg-gray-700">
                        @foreach (array_keys($importData[0]) as $columnName)
                            <th class="px-4 py-3 border-b font-semibold">{{ strtoupper($columnName) }}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @foreach (array_slice($importData, 0, 10) as $row)
                        --}}{{-- Plus besoin de sauter la ligne 1 ici --}}{{--
                        <tr>
                            @foreach ($row as $value)
                                <td class="px-4 py-2 border-b">{{ $value }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                    --}}{{-- <thead>
                        <tr class="bg-gray-100 dark:bg-gray-700">
                            @foreach ($importData[0] as $column)
                                <th class="px-4 py-3 border-b font-semibold">{{ strtoupper($column) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach (array_slice($importData, 1, 10) as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900 transition">
                                @foreach ($row as $cell)
                                    <td class="px-4 py-2">{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody> --}}{{--
                </table>
            </div>
        </div>
    @endif--}}
</x-filament-panels::page>


