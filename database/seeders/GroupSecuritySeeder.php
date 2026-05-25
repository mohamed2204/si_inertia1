<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\SousDepartement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GroupSecuritySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Récupérer des sous-départements existants pour faire les liaisons
        // (Assurez-vous d'avoir des sous-départements en BDD, ou créez-en ici)
        $sousDepartements = SousDepartement::all();

        if ($sousDepartements->isEmpty()) {
            $this->command->warn("Veuillez remplir la table sous_departements avant de lancer ce seeder.");
            return;
        }

        // On isole le premier sous-département pour simuler un cloisonnement
        $premierSousDept = $sousDepartements->first();
        $autreSousDepts = $sousDepartements->skip(1);

        // 2. Création du Groupe : DIRECTION / ADMIN
        $groupeAdmin = Group::create(['name' => 'Direction / Administration']);
        // L'admin a un accès 'total' sur absolument TOUS les sous-départements
        foreach ($sousDepartements as $sd) {
            $groupeAdmin->sousDepartements()->attach($sd->id, ['niveau_acces' => 'total']);
        }

        // 3. Création du Groupe : CHEFS DE DÉPARTEMENT
        $groupeChef = Group::create(['name' => 'Chefs de Département']);
        foreach ($sousDepartements as $sd) {
            // Le chef a souvent un accès 'total' partout pour superviser
            $groupeChef->sousDepartements()->attach($sd->id, ['niveau_acces' => 'total']);
        }

        // 4. Création du Groupe : RESPONSABLES DE SECTEUR
        $groupeResponsable = Group::create(['name' => 'Responsables de Secteur']);
        // Simulation : Il a le droit d'écriture sur le premier sous-département uniquement
        $groupeResponsable->sousDepartements()->attach($premierSousDept->id, ['niveau_acces' => 'ecriture']);
        // Il peut juste lire les autres pour information
        foreach ($autreSousDepts as $sd) {
            $groupeResponsable->sousDepartements()->attach($sd->id, ['niveau_acces' => 'lecture']);
        }

        // 5. Création du Groupe : TECHNICIENS
        $groupeTechnicien = Group::create(['name' => 'Techniciens / Préparateurs']);
        // Les techniciens ne font que lire les plannings par défaut
        foreach ($sousDepartements as $sd) {
            $groupeTechnicien->sousDepartements()->attach($sd->id, ['niveau_acces' => 'lecture']);
        }

        $this->command->info("Groupes fonctionnels et matrice pivot créés avec succès !");

        // 6. OPTIONNEL : Créer des utilisateurs de test et les affecter à ces groupes
        $this->createTestUsers($groupeAdmin, $groupeResponsable, $groupeTechnicien);
    }

    /**
     * Crée des utilisateurs de test rattachés aux groupes
     */
    private function createTestUsers(Group $groupeAdmin, Group $groupeResponsable, Group $groupeTechnicien): void
    {
        // Compte Administrateur
        $admin = User::firstOrCreate(
            ['email' => 'admin@ecrole.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );
        $admin->groups()->syncWithoutDetaching([$groupeAdmin->id]);

        // Compte Responsable (ex: Chimie)
        $responsable = User::firstOrCreate(
            ['email' => 'responsable@ecole.com'],
            [
                'name' => 'Amine Responsable',
                'password' => Hash::make('password'),
            ]
        );
        $responsable->groups()->syncWithoutDetaching([$groupeResponsable->id]);

        // Compte Technicien
        $technicien = User::firstOrCreate(
            ['email' => 'technicien@ecole.com'],
            [
                'name' => 'Youssef Technicien',
                'password' => Hash::make('password'),
            ]
        );
        $technicien->groups()->syncWithoutDetaching([$groupeTechnicien->id]);

        $this->command->info("Utilisateurs de test créés (password: 'password') !");
    }
}