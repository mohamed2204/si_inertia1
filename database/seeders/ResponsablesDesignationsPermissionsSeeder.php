<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\SousDepartement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResponsablesDesignationsPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $module = 'designations';

        // 1. Récupérer tous les groupes de type "Responsable"
        // Adaptez le 'LIKE' selon la nomenclature exacte de vos noms de groupes (ex: 'Responsable %', '%Responsable%')
        $responsableGroups = Group::where('code', 'LIKE', '%manager%')->get();

        if ($responsableGroups->isEmpty()) {
            $this->command->warn("Aucun groupe contenant 'manager' n'a été trouvé en base de données.");
            return;
        }

        // 2. Récupérer tous les sous-départements existants
        $allSousDepartements = SousDepartement::all();

        if ($allSousDepartements->isEmpty()) {
            $this->command->error("Impossible d'attribuer des droits locaux : aucun sous-département n'existe en base de données.");
            return;
        }

        $this->command->info("Début de l'attribution des droits totaux pour le module '{$module}'...");

        foreach ($responsableGroups as $group) {
            $this->command->line("Traitement du groupe : {$group->name}");

            // =========================================================================
            // A. ATTRIBUTION DES PERMISSIONS GLOBALES (permissions_groupes)
            // =========================================================================
            $actions = ['lecture', 'modification', 'suppression'];

            foreach ($actions as $action) {
                // updateOrInsert évite les doublons si vous rejouez le seeder plusieurs fois
                DB::table('permissions_groupes')->updateOrInsert(
                    [
                        'group_id'    => $group->id,
                        'module_type' => $module,
                        'type_action' => $action,
                    ],
                    [
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]
                );
            }

            // =========================================================================
            // B. ATTRIBUTION DES DROITS PIVOTS LOCAUX (group_sous_departement)
            // =========================================================================
            foreach ($allSousDepartements as $sousDept) {
                DB::table('group_sous_departement')->updateOrInsert(
                    [
                        'group_id'            => $group->id,
                        'sous_departement_id' => $sousDept->id,
                    ],
                    [
                        'niveau_acces'        => 'total', // Droit total accordé partout
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]
                );
            }
        }

        $this->command->info("Permissions 'total' appliquées avec succès pour tous les responsables !");
    }
}