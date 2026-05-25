<?php

namespace Database\Seeders;

use App\Models\Promotion;
use App\Models\Specialite;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EleveSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Promotion::all() as $promotion) {
            \App\Models\eleve::factory()
                ->count(20)
                ->create([
                    'promotion_id' => $promotion->id,
                    'specialite_id' => 1, // Assuming a default specialite_id (adjust as needed
                    'grade_id' => fake()->numberBetween(1, 1),
                    'nom' => fake()->lastName(),
                    'prenom' => fake()->firstName(),
                    'matricule' => fake()->unique()->numerify('ELEVE####'),
                    'date_naissance' => fake()->date(),
                    'nom_arabe' => fake()->name(),
                    'prenom_arabe' => fake()->lastName(),
                    'sexe' => fake()->randomElement(['M', 'F']),
                    'cni' => fake()->unique()->numerify('CNI##########'),
                    'nationalite' => fake()->country(),
                    // 'photo' => null,
                ]);
        }
    }
}
