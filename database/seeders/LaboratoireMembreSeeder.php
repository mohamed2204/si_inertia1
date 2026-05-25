<?php

namespace Database\Seeders;

use App\Models\Laboratoire;
use App\Models\Membre;
use Illuminate\Database\Seeder;

class LaboratoireMembreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Récupérer tous les IDs des membres (utilisateurs) sous forme de tableau
        $membreIds = Membre::pluck('id')->toArray();

        // Sécurité : s'il n'y a pas de membres en base, on arrête pour éviter une erreur
        if (empty($membreIds)) {
            $this->command->warn('Aucun membre trouvé. Pensez à exécuter MembreSeeder d\'abord !');
            return;
        }

        // 2. Récupérer tous les laboratoires
        $laboratoires = Laboratoire::all();

        // 3. Boucler sur chaque labo pour lui attacher tous les membres
        foreach ($laboratoires as $labo) {
            // "sync" est plus sécurisé que "attach" car il évite les doublons 
            // si tu réexécutes le seeder plusieurs fois.
            $labo->membres()->sync($membreIds);
        }

        $this->command->info('Tous les membres ont été associés à tous les laboratoires avec succès !');
    }
}