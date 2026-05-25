<?php

namespace Database\Seeders;

use App\Models\Matiere;
use App\Models\Phase;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MatiereSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Phase::all() as $phase) {
            Matiere::create([
                'nom' => 'Module ' . $phase->nom,
                // 'phase_id' => $phase->id,
                // 'coefficient' => rand(1, 4),
            ]);
        }
    }
}
