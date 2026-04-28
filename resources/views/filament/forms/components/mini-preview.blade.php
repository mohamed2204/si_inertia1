<div class="space-y-4">
    @php
        // On récupère les données depuis le composant parent
        $data = $this->importData;
        // dd($data);
        $errors = $this->validationErrors;
        $isValid = $this->isValid;
        $validationErrors = $this->validationErrors;
        $errorCount = $this->errorCount;
        $importData = $this->importData;
        $headers = $this->headers;

        $promotion_name = $this->promotion_name;
        $specialite_name = $this->specialite_name;
        $phase_name = $this->phase_name;
        $matiere_name = $this->matiere_name;

        $importError = $this->importError;
        $orphans = $this->orphans;

    @endphp

    {{-- AFFICHAGE DE L'ERREUR DE STRUCTURE --}}
  {{--  @if ($importError)
        <div class="mt-6">
            <div
                class="p-4 border rounded-xl bg-danger-50 dark:bg-danger-500/10 border-danger-200 dark:border-danger-500/20">
                <div class="flex items-center gap-3">
                    <x-filament::icon icon="heroicon-m-x-circle" class="w-6 h-6 text-danger-600 dark:text-danger-400" />

                    <h3 class="text-sm font-bold text-danger-800 dark:text-danger-400">
                        {{ $importError }}
                    </h3>
                </div>
            </div>
        </div>
    @endif--}}

    {{-- LE TABLEAU DE PREVIEW (S'affiche uniquement s'il n'y a pas d'erreur) --}}
    {{-- @if (count($importData) > 0 && !$importError) --}}
    @if (count($importData) > 0)
        <div
            class="overflow-hidden bg-white border border-gray-200 shadow-sm fi-ta-ctn rounded-xl dark:border-white/10 dark:bg-gray-900">
            <table class="w-full divide-y divide-gray-200 table-fixed dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th
                            class="px-4 py-2 text-xs font-bold tracking-wider text-gray-500 uppercase text-start dark:text-gray-400">
                            Promotion</th>
                        <th
                            class="px-4 py-2 text-xs font-bold tracking-wider text-gray-500 uppercase text-start dark:text-gray-400">
                            Spécialité</th>
                        <th
                            class="px-4 py-2 text-xs font-bold tracking-wider text-gray-500 uppercase text-start dark:text-gray-400">
                            Phase</th>
                        <th
                            class="px-4 py-2 text-xs font-bold tracking-wider text-gray-500 uppercase text-start dark:text-gray-400">
                            Matière</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">
                            {{ $this->promotion_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">
                            {{ $this->specialite_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">
                            {{ $this->phase_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">
                            {{ $this->matiere_name ?? '—' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Affichage des erreurs si présentes --}}
       {{-- @if ($errorCount > 0)
            <div class="p-4 border border-danger-200 bg-danger-50 dark:bg-danger-900/20 rounded-xl">
                <div class="flex items-center gap-2 mb-2 font-bold text-danger-800 dark:text-danger-400">
                    <x-heroicon-m-exclamation-triangle class="w-5 h-5" />
                    <span>Problèmes détectés ({{ $errorCount }})</span>
                </div>

            </div>
        @endif--}}


    @endif

    @if ($isValid && count($importData) > 0)
        <div class="mt-6">
            <h3 class="text-lg font-bold mb-4 italic text-gray-600">Aperçu du fichier ({{ count($orphans) }} à mapper)</h3>
            <div class="overflow-x-auto border rounded-lg shadow-sm">
                <table class="w-full text-left bg-white dark:bg-gray-800 text-sm">
                    <thead>
                    <tr class="bg-gray-100 dark:bg-gray-700">
                        @foreach (array_keys($importData[0]) as $columnName)
                            {{-- On cache les colonnes techniques comme status ou ids --}}
                            @if(!in_array($columnName, ['status', 'eleve_id', 'suggestion_id']))
                                <th class="px-4 py-3 border-b font-semibold">{{ strtoupper($columnName) }}</th>
                            @endif
                        @endforeach
                        <th class="px-4 py-3 border-b font-semibold text-center">ÉTAT</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach (array_slice($importData, 0, 10) as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900 transition">
                            @foreach ($row as $key => $value)
                                @if(!in_array($key, ['status', 'eleve_id', 'suggestion_id']))
                                    <td class="px-4 py-2 border-b">{{ $value }}</td>
                                @endif
                            @endforeach

                            {{-- Affichage du statut avec une pastille --}}
                            <td class="px-4 py-2 border-b text-center">
                                @if($row['status'] === 'OK')
                                    <span class="px-2 py-1 text-xs font-bold leading-none text-green-700 bg-green-100 rounded-full">Prêt</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-bold leading-none text-red-700 bg-red-100 rounded-full" title="Matricule inconnu">À Mapper</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if(count($orphans) > 0)
                <p class="mt-2 text-sm text-amber-600 font-medium">
                    ℹ️ {{ count($orphans) }} ligne(s) ne possèdent pas de matricule valide et devront être associées manuellement.
                </p>
            @endif
        </div>
    @endif

    {{-- Dans votre fichier Blade --}}
    @if(count($orphans) > 0)
        <div class="mt-4">
            {{ $this->reconcilierAction }}
        </div>
    @endif
</div>
