<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Group;
use App\Models\SousDepartement;
use Illuminate\Support\Facades\DB;

class GroupSousDepartementSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer quelques groupes et sous-départements pour le test
        $adminGroup = Group::where('code', 'admin')->first();
        $rhGroup = Group::where('code', 'chef-dept')->first();
        $managerGroup = Group::where('code', 'manager')->first();
        $techGroup = Group::where('code', 'tech')->first();

        $allSousDepts = SousDepartement::pluck('id')->toArray();

        // 1. Le groupe Admin a accès à TOUS les sous-départements en modification
        if ($adminGroup && !empty($allSousDepts)) {
            foreach ($allSousDepts as $id) {
                DB::table('group_sous_departement')->insert([
                    'group_id' => $adminGroup->id,
                    'sous_departement_id' => $id,
                    'niveau_acces' => 'modification',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 2. Le groupe RH a accès uniquement au premier sous-département en modification
        if ($rhGroup && isset($allSousDepts[0])) {
            DB::table('group_sous_departement')->insert([
                'group_id' => $rhGroup->id,
                'sous_departement_id' => $allSousDepts[0],
                'niveau_acces' => 'modification',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Le groupe Manager a un accès global en lecture seule sur le deuxième sous-département
        if ($managerGroup && isset($allSousDepts[1])) {
            DB::table('group_sous_departement')->insert([
                'group_id' => $managerGroup->id,
                'sous_departement_id' => $allSousDepts[1],
                'niveau_acces' => 'lecture',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4. Le groupe Tech a un accès global en lecture seule sur le troisième sous-département
        if ($techGroup && isset($allSousDepts[2])) {
            DB::table('group_sous_departement')->insert([
                'group_id' => $techGroup->id,
                'sous_departement_id' => $allSousDepts[2],
                'niveau_acces' => 'lecture',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}