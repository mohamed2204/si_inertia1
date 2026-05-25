<?php
namespace Database\Seeders; // Assurez-vous que cette ligne est présente

use App\Models\Departement;
use App\Models\Laboratoire;
use App\Models\LaboratoireConfig;
use App\Models\SousDepartement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LabSeeder extends Seeder
{
    public function run()
    {
        // Désactiver les logs de requêtes pour gagner en performance (important pour PostgreSQL)
        DB::connection()->disableQueryLog();

        $jours       = ['Vendredi', 'Samedi', 'Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi'];
        $nomsMembres = ['mem 1', 'mem 2', 'mem 3', 'mem 4', 'mem 5'];

        for ($i = 1; $i <= 20; $i++) {
            $this->command->getOutput()->info("Département $i/20");
            $dept = Departement::create([
                'nom' => "Département " . str_pad($i, 2, '0', STR_PAD_LEFT),
                // 'created_at' => now(),
                // 'updated_at' => now(),
            ]);

            for ($j = 1; $j <= 3; $j++) {
                $this->command->getOutput()->info("  Sous-Département $j/3 du Département $i");
                $sousDept = SousDepartement::create([
                    'departement_id' => $dept->id,
                    'nom'            => "Sous-Département " . $i . "." . $j,
                    // 'created_at'    => now(),
                    // 'updated_at'    => now(),
                ]);

                for ($k = 1; $k <= 9; $k++) {
                    $this->command->getOutput()->info("  Laboratoire $k/9 du Sous-Département $j");
                    $lab = Laboratoire::create([
                        'sous_departement_id' => $sousDept->id,
                        'nom'                 => "Labo " . $k . " (" . $sousDept->nom . ")",
                    ]);

                    // Création de la config pour chaque jour de la semaine
                    foreach ($jours as $indexJour => $jour) {
                        $this->command->getOutput()->info("    Config pour $jour du Laboratoire $k");
                        $config = LaboratoireConfig::create([
                            'laboratoire_id'  => $lab->id,
                            'jour'            => $jour,
                            'jour_label'      => $jour,
                            'ordre_affichage' => $indexJour,
                            'type_config'     => 'Calendrier',
                        ]);

                        // Insertion des membres requis avec gestion de l'ordre
                        foreach ($nomsMembres as $indexMembre => $nom) {
                            $this->command->getOutput()->info("      Requis $nom pour $jour du Laboratoire $k");
                            $config->requis()->create([
                                'lab_config_id'  => $config->id,
                                'libelle'        => $nom,
                                'is_obligatoire' => true,
                                'ordre'          => $indexMembre + 1, // Pour votre orderBy('ordre')
                            ]);
                        }
                    }
                }
            }
        }
    }
}
