<?php

namespace Database\Seeders;

use App\Models\RoleTache;
use Illuminate\Database\Seeder;

class RoleTacheSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            // Le cycle hebdomadaire (Ordre 1 à 7)
            ['libelle' => 'Vendredi', 'ordre' => 1, 'categorie' => 'jour'],
            ['libelle' => 'Samedi',   'ordre' => 2, 'categorie' => 'jour'],
            ['libelle' => 'Dimanche', 'ordre' => 3, 'categorie' => 'jour'],
            ['libelle' => 'Lundi',    'ordre' => 4, 'categorie' => 'jour'],
            ['libelle' => 'Mardi',    'ordre' => 5, 'categorie' => 'jour'],
            ['libelle' => 'Mercredi', 'ordre' => 6, 'categorie' => 'jour'],
            ['libelle' => 'Jeudi',    'ordre' => 7, 'categorie' => 'jour'],

            // Les rôles de responsables (Ordre 10+)
            ['libelle' => 'Responsable 1', 'ordre' => 10, 'categorie' => 'responsable'],
            ['libelle' => 'Responsable 2', 'ordre' => 11, 'categorie' => 'responsable'],

            // Les remplaçants
            ['libelle' => 'Remplaçant 1', 'ordre' => 20, 'categorie' => 'remplacant'],
            ['libelle' => 'Remplaçant 2', 'ordre' => 21, 'categorie' => 'remplacant'],
        ];

        foreach ($roles as $role) {
            RoleTache::updateOrCreate(
                ['libelle' => $role['libelle']], // Évite les doublons si on relance le seed
                $role
            );
        }
    }
}
