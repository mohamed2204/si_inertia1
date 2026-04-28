<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Specialite;
use App\Models\Phase;
use App\Models\Matiere;
use App\Models\ProgrammeMatiere;

class ProgrammeMatiereSeeder extends Seeder
{
    public function run(): void
    {
        $phases = Phase::orderBy('ordre')->get();
        $matieres = Matiere::all();

        Specialite::chunk(20, function ($specialites) use ($phases, $matieres) {

            foreach ($specialites as $specialite) {
                foreach ($phases as $phase) {

                    $matieresPhase = $this->matieresPourPhase($matieres, $phase->ordre);

                    foreach ($matieresPhase as $matiere) {
                        ProgrammeMatiere::updateOrCreate(
                            [
                                'specialite_id' => $specialite->id,
                                'phase_id' => $phase->id,
                                'matiere_id' => $matiere->id,
                            ],
                            [
                                'coefficient' => $this->coefficientParPhase($phase->ordre),
                            ]
                        );
                    }
                }
            }
        });
    }

    /**
     * Définition métier : matières par phase
     */
    private function matieresPourPhase($matieres, int $ordre)
    {
        return match ($ordre) {
            1 => $matieres->take(3), // phase d’initiation
            2 => $matieres->take(4), // phase intermédiaire
            3 => $matieres,          // phase avancée
            default => $matieres->take(2),
        };
    }

    /**
     * Pondération par phase
     */
    private function coefficientParPhase(int $ordre): int
    {
        return match ($ordre) {
            1 => 1,
            2 => 2,
            3 => 3,
            default => 1,
        };
    }
}


// namespace Database\Seeders;

// use Illuminate\Database\Seeder;
// use App\Models\Specialite;
// use App\Models\Phase;
// use App\Models\Matiere;
// use App\Models\ProgrammeMatiere;

// class ProgrammeMatiereSeeder extends Seeder
// {
//     public function run(): void
//     {
//         $phases = Phase::orderBy('ordre')->get();
//         $matieres = Matiere::all();

//         Specialite::chunk(20, function ($specialites) use ($phases, $matieres) {

//             foreach ($specialites as $specialite) {

//                 foreach ($phases as $phase) {

//                     // 🔧 règle métier : matières par phase
//                     $matieresPhase = $this->matieresParPhase($matieres, $phase->ordre);

//                     foreach ($matieresPhase as $matiere) {

//                         ProgrammeMatiere::updateOrCreate(
//                             [
//                                 'specialite_id' => $specialite->id,
//                                 'phase_id' => $phase->id,
//                                 'matiere_id' => $matiere->id,
//                             ],
//                             [
//                                 'coefficient' => $this->coefficient($phase->ordre),
//                             ]
//                         );
//                     }
//                 }
//             }
//         });
//     }

//     /**
//      * Détermine les matières d’une phase donnée
//      */
//     protected function matieresParPhase($matieres, int $ordre)
//     {
//         return match ($ordre) {
//             1 => $matieres->whereIn('nom', [
//                 'Mathématiques',
//                 'Informatique',
//                 'Français',
//             ]),
//             2 => $matieres->whereIn('nom', [
//                 'Mathématiques',
//                 'Informatique',
//                 'Physique',
//             ]),
//             3 => $matieres->whereIn('nom', [
//                 'Informatique',
//                 'Projet',
//             ]),
//             default => $matieres->take(3),
//         };
//     }

//     /**
//      * Coefficient par phase
//      */
//     protected function coefficient(int $ordre): int
//     {
//         return match ($ordre) {
//             1 => rand(1, 2),
//             2 => rand(2, 3),
//             3 => rand(3, 4),
//             default => 1,
//         };
//     }
// }
