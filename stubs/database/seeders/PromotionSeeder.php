<?php

namespace Database\Seeders;

use App\Models\Promotion;
use App\Models\Specialite;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $specialites = Specialite::all();

        foreach ($specialites as $specialite) {
            Promotion::create([
                'nom' => 'Promo ' . now()->year . ' - ' . $specialite->nom,
            ]);
        }
    }
}
