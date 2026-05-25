<?php

namespace Database\Seeders;

use App\Models\Phase;
use App\Models\Promotion;
use App\Models\PromotionSpecialitePhase;
use App\Models\Specialite;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PromotionSpecialitePhaseSeeder extends Seeder
{
    public function run(): void
    {
        $phases = Phase::orderBy('ordre')->get();

        foreach (Promotion::all() as $promotion) {
            $start = Carbon::parse($promotion->date_debut);

            foreach ($phases as $phase) {
                $end = (clone $start)->addMonths(8);

                PromotionSpecialitePhase::create([
                    'promotion_id' => $promotion->id,
                    'specialite_id' => Specialite::first()->id,
                    'phase_id' => $phase->id,
                    'date_debut' => $start,
                    'date_fin' => $end,
                ]);

                $start = (clone $end)->addDay();
            }
        }
    }
}
