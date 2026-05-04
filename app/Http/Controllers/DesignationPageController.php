<?php
namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\Designation;
use App\Models\DesignationItem;
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
    public function index()
    {
        $dataReturned = Inertia::render('Designations/DesignationsPage', [
            'designations'     => Designation::with(['sousDepartement.departement', 'items.laboratoire', 'items.membre'])->get(),
            'departements'     => Departement::all(),
            'sousDepartements' => SousDepartement::all(),
            // On charge les labos avec leurs requis (rôles et quotas)
            'laboratoires'     => Laboratoire::with('labRequis.roleTache')->get(),
            'membres'          => Membre::select('id', 'nom')->get(),
        ]);

        //dd($dataReturned);

        return $dataReturned;
    }

    /**
     * Enregistre une nouvelle planification
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'semaine_nom'         => 'required|string|max:255',
            'date_debut'          => 'required|date',
            'sous_departement_id' => 'required|exists:sous_departements,id',
            'labs_data'           => 'required|array',
        ]);

        try {
            DB::beginTransaction();

            // 1. Création de l'entête de la désignation
            $designation = Designation::create([
                'semaine_nom'         => $validated['semaine_nom'],
                'date_debut'          => $validated['date_debut'],
                'sous_departement_id' => $validated['sous_departement_id'],
                'user_id'             => auth()->id(), // Si vous avez une gestion d'utilisateurs
            ]);

            // 2. Enregistrement des items par laboratoire (les onglets)
            foreach ($request->labs_data as $labId => $affectations) {
                foreach ($affectations as $item) {
                    // On n'enregistre que si un membre est sélectionné
                    if (! empty($item['membre_id'])) {
                        DesignationItem::create([
                            'designation_id' => $designation->id,
                            'laboratoire_id' => $labId,
                            'role_tache_id'  => $item['role_id'],
                            'slot'           => $item['slot'], // Pour gérer les quotas multiples
                            'membre_id'      => $item['membre_id'],
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Désignation créée avec succès !');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
        }
    }

    /**
     * Prépare les données pour la modification
     */
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
