<?php

namespace Database\Seeders;

use App\Models\ParentUser;
use App\Models\Promotion;
use App\Models\PromotionSpecialitePhase;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name'  => 'admin',
        //     'email' => 'admin@example.com',
        // ]);

        // ParentUser::create([
        //     'nom'     => 'Parent Test',
        //     'email'    => 'parent@test.com',
        //     'password' => Hash::make('password123'),
        // ]);

        // $this->call(EleveSeeder::class);

        // Schema::disableForeignKeyConstraints();

        // // Truncate the specific table
        // DB::table('notes')->truncate();
        // DB::table('eleves')->truncate();
        // DB::table('promotion_specialite_phases')->truncate();
        // DB::table('promotion_specialites')->truncate();
        // DB::table('programme_matieres')->truncate();
        // DB::table('specialites')->truncate();
        // DB::table('phases')->truncate();
        // DB::table('promotions')->truncate();
        // DB::table('matieres')->truncate();


        $this->call([
                // PromotionSeeder::class,
                // SpecialiteSeeder::class,
                // PromotionSpecialitesSeeder::class,
                // PhaseSeeder::class,
                // PromotionSpecialitePhaseSeeder::class,
                // MatiereSeeder::class,
                // ProgrammeMatiereSeeder::class,
                // EleveSeeder::class,
            NotesSeeder::class,
        ]);

        // Optional: Re-enable foreign key constraints
        // Schema::enableForeignKeyConstraints();
    }
}
