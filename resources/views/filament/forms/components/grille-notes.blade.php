<div class="overflow-hidden border border-gray-300 dark:border-gray-700 rounded-xl shadow-sm">
    <table class="w-full text-left border-collapse bg-white dark:bg-gray-900">
        <thead>
        <tr class="bg-gray-100 dark:bg-gray-800 border-b border-gray-300 dark:border-gray-700">
            <th class="px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider"> Élève
            </th>
            <th class="px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider text-center w-32">
                Note / 20
            </th>
        </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
        @if($getState() && is_array($getState()))
            @foreach($getState() as $uuid => $row)
                {{-- Correction : hover sombre en dark mode pour ne pas "effacer" le texte blanc --}}
                <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                        {{ $row['eleve_nom'] ?? 'Élève inconnu' }}
                    </td>
                    <td class="px-4 py-2">
                        <div class="flex justify-center">
                            <input
                                type="number"
                                step="0.25"
                                min="0"
                                max="20"
                                required
                                wire:model.defer="{{ $getStatePath() }}.{{ $uuid }}.valeur"
                                {{-- Empêche de taper au-delà de 20 et bloque les nombres négatifs --}}
                                oninput="if(this.value > 20) this.value = 20; if(this.value < 0) this.value = 0;"
                                {{-- On ajoute onkeypress pour empêcher les signes '-' --}}
                                onkeypress="return (event.charCode != 45)"
                                {{-- Correction : On force le texte blanc et un fond sombre pour l'input en dark mode --}}
                                class="block w-24 text-center text-sm font-bold py-1.5
                               bg-white dark:bg-gray-800
                               text-gray-900 dark:text-white
                               border border-gray-300 dark:border-gray-600
                               rounded-lg focus:ring-primary-500 focus:border-primary-500 transition-all shadow-sm"
                            >
                        </div>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="2" class="px-4 py-8 text-center text-gray-500 italic">
                    Veuillez sélectionner une matière pour afficher les élèves.
                </td>
            </tr>
        @endif
        </tbody>
    </table>
</div>
