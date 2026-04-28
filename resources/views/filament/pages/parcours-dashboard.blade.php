<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <x-filament::card>
            <p class="text-lg font-bold">Élèves</p>
            <p class="text-3xl">{{ new App\Models\Eleve()->count() }}</p>
        </x-filament::card>

        <x-filament::card>
            <p class="text-lg font-bold">Spécialités</p>
            <p class="text-3xl">{{ new App\Models\Specialite()->count() }}</p>
        </x-filament::card>

        <x-filament::card>
            <p class="text-lg font-bold">Promotions</p>
            <p class="text-3xl">{{ new App\Models\Promotion()->count() }}</p>
        </x-filament::card>
    </div>
</x-filament-panels::page>
