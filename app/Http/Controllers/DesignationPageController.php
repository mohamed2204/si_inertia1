<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\Designation;
use App\Models\Laboratoire;
use App\Models\Membre;
use App\Models\SousDepartement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DesignationPageController extends Controller
{
    /**
     * Affiche la liste et les données nécessaires au formulaire
     */
    // public function index()
    // {
    //     $dataReturned = Inertia::render('Designations/DesignationsPage', [
    //         'designations'     => Designation::with(['sousDepartement.departement', 'items.laboratoire', 'items.membre'])->get(),
    //         'departements'     => Departement::all(),
    //         'sousDepartements' => SousDepartement::all(),
    //         // On charge les labos avec leurs requis (rôles et quotas)
    //         'laboratoires'     => Laboratoire::with('labRequis.roleTache')->get(),
    //         'membres'          => Membre::select('id', 'nom')->get(),
    //     ]);

    //     //dd($dataReturned);

    //     return $dataReturned;
    // }

    // public function index()
    // {
    //     return Inertia::render('Designations/DesignationsPage', [
    //         // Chargement des départements avec leurs sous-départements et labos rattachés
    //         'departements' => Departement::with([
    //             'sousDepartements.laboratoires.labRequis.roleTache',
    //         ])->get(),

    //         // Liste simple pour les Dropdowns de sélection
    //         'membres'      => Membre::select('id', 'nom')->orderBy('nom')->get(),

    //         // Historique des désignations existantes (si besoin d'affichage)
    //         'designations' => Designation::with([
    //             'sousDepartement.departement',
    //             'items.laboratoire',
    //             'items.membre',
    //         ])->latest()->get(),
    //     ]);
    // }

    public function index()
    {
        return Inertia::render('TestLayout', [
            // Chargement de la nouvelle hiérarchie : Lab -> Jours -> Postes (Requis)
            'departements' => Departement::with([
                'sousDepartements.laboratoires.config_jours.requis'
            ])->get(),

            'membres' => Membre::select('id', 'nom')
                ->orderBy('nom')
                ->get(),

            'designations' => Designation::with([
                'sousDepartement.departement',
                // On adapte aussi le chargement de l'historique si nécessaire
                'items.laboratoire',
                'items.membre',
            ])
                ->latest()
                ->paginate(10)
                ->withQueryString(),
        ]);

        return Inertia::render('Designations/DesignationsPage', [
            // Chargement de la nouvelle hiérarchie : Lab -> Jours -> Postes (Requis)
            'departements' => Departement::with([
                'sousDepartements.laboratoires.config_jours.requis'
            ])->get(),

            'membres' => Membre::select('id', 'nom')
                ->orderBy('nom')
                ->get(),

            'designations' => Designation::with([
                'sousDepartement.departement',
                // On adapte aussi le chargement de l'historique si nécessaire
                'items.laboratoire',
                'items.membre',
            ])
                ->latest()
                ->paginate(10)
                ->withQueryString(),
        ]);
    }
    /**
     * Enregistre une nouvelle planification
     */

    public function store(Request $request)
    {
        $designations = $request->input('designations');

        // On utilise une transaction pour s'assurer que tout est enregistré ou rien du tout
        DB::transaction(function () use ($designations) {

            foreach ($designations as $labId => $jours) {
                foreach ($jours as $jour => $postes) {
                    foreach ($postes as $requisId => $membreId) {

                        // On met à jour ou on crée l'affectation
                        // Table: designations (id, lab_id, jour, requis_id, membre_id, date_semaine)
                        Designation::updateOrCreate(
                            [
                                'lab_id'       => $labId,
                                'jour'         => $jour,
                                'requis_id'    => $requisId,
                                'date_semaine' => $this->getCurrentWeekDate(), // ex: 2024-10-26
                            ],
                            [
                                'membre_id' => $membreId,
                            ]
                        );
                    }
                }
            }
        });

        return redirect()->back()->with('success', 'Planning enregistré avec succès !');
    }

    public function edit($id)
    {
        // 1. Récupérer la désignation avec ses items
        $designation = Designation::with(['items.membre', 'sous_departement.departement'])->findOrFail($id);

        // 2. REFORMATAGE (Le bloc de code en question)
        // On transforme les lignes SQL en structure "labs_data" pour React
        $labsData = $designation->items->groupBy('laboratoire_id')->map(function ($items) {
            return $items->map(function ($item) {
                return [
                    'role_id'   => (int) $item->role_tache_id,
                    'slot'      => (int) $item->slot,
                    'membre_id' => (int) $item->membre_id,
                ];
            });
        });

        // 3. Envoi à Inertia
        return Inertia::render('DesignationsPage', [
            'designation' => $designation, // L'entête (date, nom...)
            'editMode'    => true,
            'initialData' => $labsData, // Les affectations reformatées
            // ... vos autres props (departements, membres, etc.)
        ]);
    }
    /**
     * Met à jour une planification existante
     */
    public function update(Request $request, $id)
    {
        $designation = Designation::findOrFail($id);

        $validated = $request->validate([
            'semaine_nom'         => 'required|string',
            'date_debut'          => 'required|date',
            'sous_departement_id' => 'required|exists:sous_departements,id',
            'labs_data'           => 'required|array',
        ]);

        try {
            DB::beginTransaction();

            // 1. Mise à jour de l'entête
            $designation->update([
                'semaine_nom'         => $validated['semaine_nom'],
                'date_debut'          => $validated['date_debut'],
                'sous_departement_id' => $validated['sous_departement_id'],
            ]);

            // 2. Supprimer les anciens items pour reconstruire la nouvelle grille
            // C'est la méthode la plus propre pour gérer les changements de quotas/membres
            $designation->items()->delete();

            // 3. Ré-insertion des nouvelles données
            foreach ($request->labs_data as $labId => $affectations) {
                foreach ($affectations as $item) {
                    if (! empty($item['membre_id'])) {
                        $designation->items()->create([
                            'laboratoire_id' => $labId,
                            'role_tache_id'  => $item['role_id'],
                            'slot'           => $item['slot'],
                            'membre_id'      => $item['membre_id'],
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Désignation mise à jour !');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erreur lors de la mise à jour.');
        }
    }
}
