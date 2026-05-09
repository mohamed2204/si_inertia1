<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Laboratoire;
use App\Models\LaboratoireConfig;
use App\Models\LaboratoireConfigRequis;
use Illuminate\Support\Facades\DB;

class LaboratoireConfigSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Nettoyage sécurisé pour repartir à zéro
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        LaboratoireConfig::truncate();
        LaboratoireConfigRequis::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Définition du cycle hebdomadaire
        $semaine = [
            ['jour' => 'ven', 'label' => 'Vendredi', 'ordre' => 1],
            ['jour' => 'sam', 'label' => 'Samedi',   'ordre' => 2],
            ['jour' => 'dim', 'label' => 'Dimanche', 'ordre' => 3],
            ['jour' => 'lun', 'label' => 'Lundi',    'ordre' => 4],
            ['jour' => 'mar', 'label' => 'Mardi',    'ordre' => 5],
            ['jour' => 'mer', 'label' => 'Mercredi', 'ordre' => 6],
            ['jour' => 'jeu', 'label' => 'Jeudi',    'ordre' => 7],
        ];

        // 3. Récupération des laboratoires
        $laboratoires = Laboratoire::all();

        foreach ($laboratoires as $lab) {
            foreach ($semaine as $info) {
                // Création de la configuration du jour
                $config = LaboratoireConfig::create([
                    'laboratoire_id'  => $lab->id,
                    'jour'            => $info['jour'],
                    'jour_label'      => $info['label'],
                    'ordre_affichage' => $info['ordre'],
                ]);

                // 4. Création du requis selon votre schéma exact
                LaboratoireConfigRequis::create([
                    'lab_config_id' => $config->id,
                    'libelle'               => 'Titulaire',   // Correspond à votre colonne 'libelle'
                    'ordre'                 => 1,             // Correspond à votre colonne 'ordre'
                    'is_obligatoire'        => true,          // Correspond à votre colonne 'is_obligatoire'
                ]);
            }
        }

        $this->command->info("Configuration et requis créés avec succès pour tous les laboratoires.");
    }
}
