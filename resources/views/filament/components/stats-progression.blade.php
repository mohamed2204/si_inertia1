<div class="max-h-[250px] overflow-y-auto pr-2 space-y-4 p-4 bg-gray-50/50 rounded-xl scrollbar-thin scrollbar-thumb-gray-300">

    @php
        // Si $stats est une Closure, on doit l'exécuter en lui passant le record
        if ($stats instanceof \Closure) {
            // Dans une Infolist, Filament met l'objet record à disposition via $getRecord()
            // ou on peut essayer de l'exécuter à vide si l'accessor n'en a pas besoin
            $stats = $stats($getRecord() ?? null);
        }

        // Sécurité supplémentaire : transformer en tableau si c'est un objet
        $statsData = (array) $stats;
    @endphp

    <h3 class="text-sm font-black text-slate-800 mb-4 italic">
        Tableau de bord : {{ $stats['nom_secteur'] ?? 'Secteur' }}
    </h3>

    @foreach($stats['labs'] as $lab)

        @php
            // On définit manuellement les codes couleurs (Hex)
            $hexColor = '#9ca3af'; // Gris par défaut (gray-400)

            if ($lab->percentage < 30) {
                $hexColor = '#ef4444'; // Rouge (danger)
            } elseif ($lab->percentage < 100) {
                $hexColor = '#fb923c'; // Orange (warning)
            } else {
                $hexColor = '#10b981'; // Vert (success)
            }
        @endphp

        <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex justify-between items-center mb-3">
                <span class="text-sm font-bold text-slate-700 dark:text-gray-200 flex items-center gap-2">
                    {{ $lab->nom }}
                    @if($lab->complet)
                        <x-heroicon-s-check-circle class="w-4 h-4 text-success-500" />
                    @endif
                </span>
                <span class="text-xs font-medium text-slate-500">
                    <span class="font-bold text-slate-800 dark:text-white">{{ $lab->saisis }}</span> / {{ $lab->total }} membres
                </span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-6 dark:bg-gray-900 overflow-hidden relative">
                <div class="h-6 rounded-full transition-all duration-1000 flex items-center justify-end pr-3"
                     style="width: {{ $lab->percentage }}%; background-color: {{ $hexColor }};">

                 <span class="text-[10px] font-black text-white">
                    {{ $lab->percentage }}%
                 </span>
                </div>
            </div>

        </div>
    @endforeach

    <div class="mt-6 bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-md border-2 border-primary-50 dark:border-primary-900/30 flex flex-col items-center justify-center">
        <span class="text-4xl font-black text-primary-600 tracking-tight">
            Global : {{ $stats['total_global'] }}%
        </span>
    </div>
</div>
