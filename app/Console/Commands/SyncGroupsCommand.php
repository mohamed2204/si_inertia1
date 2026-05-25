<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\SousDepartement;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncGroupsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-groups-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Placez votre logique ici
        $subDepts = SousDepartement::all();

        foreach ($subDepts as $sd) {
            $group = Group::firstOrCreate(
                ['code' => 'GRP-' . Str::upper(Str::slug($sd->nom))],
                ['name' => 'Équipe ' . $sd->nom]
            );
            $group->sousDepartements()->syncWithoutDetaching([$sd->id]);
        }

        $this->info('Groupes synchronisés avec succès !');
    }
}
