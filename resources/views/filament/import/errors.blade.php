<div x-data>

    <!-- Résumé -->
    <div
        x-show="$wire.importErrors.length > 0"
        x-cloak
        class="p-3 mb-4 rounded bg-danger-50 text-danger-700"
    >
        <strong>
            {{ __('Attention') }} :
            <span x-text="$wire.importErrors.length"></span>
            erreur(s) détectée(s)
        </strong>
    </div>
    <div x-show="$wire.importErrors.length > 0" class="mt-6">
        <h3 class="text-lg font-bold mb-4 italic text-gray-600">Aperçu des ereurs du fichier</h3>
        <div class="overflow-x-auto border rounded-lg shadow-sm">
            <!-- Tableau -->
            <table
                x-show="$wire.importErrors.length > 0"
                class="w-full text-sm border bg-gray-100 dark:bg-gray-700">
                <thead>
                <tr bg-gray-100 dark:bg-gray-700>
                    <th class="px-4 py-3 border-b font-semibold">Ligne</th>
                    <th class="px-4 py-3 border-b font-semibold">Champ</th>
                    <th class="px-4 py-3 border-b font-semibold">Message</th>
                </tr>
                </thead>
                <tbody>
                <template x-for="error in $wire.importErrors" :key="error.line + error.field">
                    <tr>
                        <td class="px-4 py-2 border-b" x-text="error.line"></td>
                        <td class="px-4 py-2 border-b" x-text="error.field"></td>
                        <td class="px-4 py-2 border-b text-danger-600" x-text="error.message"></td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Badge -->
    <div
        x-show="$wire.importErrors.length > 0"
        class="mt-2 inline-flex items-center px-2 py-1 text-xs font-bold rounded bg-danger-600 text-white"
    >
        <span x-text="$wire.importErrors.length"></span>
        erreur(s)
    </div>

</div>
