<?php

namespace Database\Seeders;

use App\Models\Phase;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PhaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Phase::insert([
            ['nom' => 'CAT 1', 'ordre' => 1],
            ['nom' => 'CAT 2', 'ordre' => 2],
            ['nom' => 'BE', 'ordre' => 3],
        ]);
    }
}
