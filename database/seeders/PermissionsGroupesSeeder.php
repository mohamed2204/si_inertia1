<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Group;
use App\Models\Designation;
use Illuminate\Support\Facades\DB;

class PermissionsGroupesSeeder extends Seeder
{
    public function run(): void
    {
        $adminGroup = Group::where('code', 'admin')->first();
        $rhGroup = Group::where('code', 'chef-dept')->first();
        $techGroup = Group::where('code', 'tech')->first();
        $managerGroup = Group::where('code', 'manager')->first();

        // Récupérer une désignation spécifique pour l'exemple ciblé
        $designationId = Designation::value('id') ?? 1; 

        $permissions = [];

        // --- Permissions pour le Groupe RH ---
        if ($rhGroup) {
            // RH peut lire globalement le module des désignations (module_id à null)
            $permissions[] = [
                'group_id' => $rhGroup->id,
                'type_action' => 'lecture',
                'module_type' => 'designations', // Identifiant texte du module global
                'module_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // RH peut modifier uniquement une désignation très spécifique (Ex: ID de la désignation)
            $permissions[] = [
                'group_id' => $rhGroup->id,
                'type_action' => 'modification',
                'module_type' => Designation::class, // Utilisation du namespace du Modèle (recommandé par Laravel)
                'module_id' => $designationId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // --- Permissions pour le Groupe Admin ---
        if ($adminGroup) {
            // L'admin a les droits absolus sur les modules de base (Ex: gestion des utilisateurs)
            $permissions[] = [
                'group_id' => $adminGroup->id,
                'type_action' => 'suppression',
                'module_type' => 'users',
                'module_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($managerGroup) {
            // Manager a un droit de lecture globale sur les désignations
            $permissions[] = [
                'group_id' => $managerGroup->id,
                'type_action' => 'lecture',
                'module_type' => 'designations',
                'module_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($techGroup) {
            // Tech a un droit de lecture globale sur les désignations
            $permissions[] = [
                'group_id' => $techGroup->id,
                'type_action' => 'lecture',
                'module_type' => 'designations',
                'module_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        // Insertion en masse dans la table pivot polymorphe
        if (!empty($permissions)) {
            DB::table('permissions_groupes')->insert($permissions);
        }
    }
}