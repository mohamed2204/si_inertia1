<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Promotion;
use App\Models\Specialite;
use Illuminate\Support\Facades\DB;

class PromotionSpecialitesSeeder extends Seeder
{
    public function run(): void
    {
        $specialites = Specialite::all();

        Promotion::chunk(20, function ($promotions) use ($specialites) {

            foreach ($promotions as $promotion) {

                foreach ($specialites as $specialite) {

                    DB::table('promotion_specialites')->updateOrInsert(
                        [
                            'promotion_id' => $promotion->id,
                            'specialite_id' => $specialite->id,
                        ],
                        []
                    );
                }
            }
        });
    }
}
