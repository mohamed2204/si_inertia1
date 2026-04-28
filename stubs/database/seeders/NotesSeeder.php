<?php

namespace Database\Seeders;

use App\Models\Eleve;
use Illuminate\Database\Seeder;
use App\Models\Note;
use Faker\Factory as Faker;

class NotesSeeder extends Seeder
{
    public function run(): void
    {
        // // Pour chaque promotion + phase planifiée
        // PromotionPhase::with(['promotion', 'phase'])->each(function ($promotionPhase) {

        //     // Élèves de la promotion
        //     $eleves = Eleve::where('promotion_id', $promotionPhase->promotion_id)->get();

        //     // Programme pédagogique de la phase (par spécialité)
        //     $programmes = ProgrammeMatiere::where('specialite_id', $promotionPhase->promotion->specialite_id)
        //         ->where('phase_id', $promotionPhase->phase_id)
        //         ->get();

        //     foreach ($eleves as $eleve) {
        //         foreach ($programmes as $programme) {

        //             Note::firstOrCreate(
        //                 [
        //                     'eleve_id'   => $eleve->id,
        //                     'phase_id'   => $promotionPhase->phase_id,
        //                     'matiere_id' => $programme->matiere_id,
        //                 ],
        //                 [
        //                     // Notes réalistes entre 6 et 18
        //                     'valeur' => rand(60, 180) / 10,
        //                 ]
        //             );
        //         }
        //     }
        // });
        $faker = Faker::create('fr_FR');

        Eleve::with([
            'specialite.programmeMatieres'
        ])->chunk(50, function ($eleves) use ($faker) {

            foreach ($eleves as $eleve) {

                foreach ($eleve->specialite->programmeMatieres as $programmeMatiere) {

                    // éviter les doublons
                    $exists = Note::where('eleve_id', $eleve->id)
                        ->where('programme_matiere_id', $programmeMatiere->id)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    Note::create([
                        'eleve_id' => $eleve->id,
                        'programme_matiere_id' => $programmeMatiere->id,
                        'valeur' => $faker->randomFloat(2, 6, 18),
                    ]);
                }
            }
        });
    }
}
