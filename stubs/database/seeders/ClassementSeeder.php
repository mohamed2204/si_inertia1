<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Classement;
use App\Models\Eleve;
use App\Models\Phase;

class ClassementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $eleves = Eleve::all();
        $phases = Phase::all();

        foreach ($eleves as $eleve) {
            foreach ($phases as $phase) {
                Classement::updateOrCreate(
                    [
                        'eleve_id' => $eleve->id,
                        'phase_id' => $phase->id,
                        'promotion_id' => $eleve->promotion_id,
                        'specialite_id' => $eleve->specialite_id,
                    ],
                    [
                        'moyenne' => rand(10, 20),
                        'rang' => null, // calculer après
                    ]
                );
            }
        }

    }
}
