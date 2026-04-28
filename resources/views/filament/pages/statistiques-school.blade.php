<x-filament::page>
<<<<<<< HEAD
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <x-filament::card>
            <p class="text-lg font-bold">Élèves</p>
            <p class="text-3xl">{{ (new App\Models\Elefe)->count() }}</p>
=======
    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <x-filament::card>
            <p class="text-lg font-bold">Élèves</p>
            <p class="text-3xl">{{ new App\Models\Eleve()->count() }}</p>
>>>>>>> a1075986f8511c7353b700aa8dbe6a25b5bf530a
        </x-filament::card>

        <x-filament::card>
            <p class="text-lg font-bold">Spécialités</p>
<<<<<<< HEAD
            <p class="text-3xl">{{ (new App\Models\Specialite)->count() }}</p>
=======
            <p class="text-3xl">{{ new App\Models\Specialite()->count() }}</p>
>>>>>>> a1075986f8511c7353b700aa8dbe6a25b5bf530a
        </x-filament::card>

        <x-filament::card>
            <p class="text-lg font-bold">Promotions</p>
<<<<<<< HEAD
            <p class="text-3xl">{{ (new App\Models\Promotion)->count() }}</p>
        </x-filament::card>


    </div>
=======
            <p class="text-3xl">{{ new App\Models\Promotion()->count() }}</p>
        </x-filament::card>
    </div>
    {{-- <div>
        <h1>Planning des promotions</h1> --}}

    {{-- On inclut le fichier situé dans resources/views/partials/legend.blade.php --}}
    {{-- @include('filament.widgets.roadmap-widget') --}}
    {{-- </div> --}}
>>>>>>> a1075986f8511c7353b700aa8dbe6a25b5bf530a
</x-filament::page>
