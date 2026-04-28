<?php

namespace Database\Seeders;

use App\Models\Specialite;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpecialiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Specialite::insert([
            ['nom' => 'Programmation'],
            ['nom' => 'Gestion Administrative'],
            ['nom' => 'Secrétariat'],
            ['nom' => 'Réseaux et Télécommunications'],
            ['nom' => 'Informatique de Gestion'],
            ['nom' => 'Multimédia'],
            ['nom' => 'Comptabilité'],
            ['nom' => 'Marketing'],
        ]);
    }
}
