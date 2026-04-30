<?php
namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\Designation;
use App\Models\Laboratoire;
use App\Models\Membre;
use App\Models\SousDepartement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DesignationController extends Controller
{
    // Appelé par la route GET
    public function index()
    {
        $data = Inertia::render('Designations/Index', [
            // On charge les désignations avec leurs relations pour la DataTable
            'designations'     => Designation::with([
                'sousDepartement.departement',
                'items.membre',
                'items.laboratoire', // Indispensable pour les badges
            ])->latest()->get(),

            // Données pour les formulaires (Dropdowns et MultiSelect)
            'departements'     => Departement::all(),

            'sousDepartements' => SousDepartement::all(),

            'laboratoires'     => Laboratoire::with(['labRequis' => function ($query) {
                $query->orderBy('ordre', 'asc'); // Force l'ordre au cas où
            }, 'labRequis.roleTache'])->get(),
            
            //'membres'          => User::select('id', DB::raw("name as nom_complet"))->get(), // Ajustez selon votre table
            'membres'          => Membre::all(), // Liste pour le MultiSelect
        ]);
        // dd($data);
        return $data;
    }

    public function store(Request $request)
    {
        return $this->saveDesignation($request);
    }

    public function update(Request $request, $id)
    {
        return $this->saveDesignation($request, $id);
    }

    protected function saveDesignation(Request $request, $id = null)
    {

        //dd($request->all());

        // 1. AJOUTER LA VALIDATION
        $validated = $request->validate([
            //'semaine_nom'         => 'required|string|max:255',
            'sous_departement_id' => 'required|exists:sous_departements,id',
            // On peut rendre la date requise ici si vous avez un champ date dans React
            'date_debut'          => 'required|date',
        ]);

        DB::transaction(function () use ($request, $id) {
            // 1. Créer ou trouver la désignation
            $designation = $id ? Designation::findOrFail($id) : new Designation();

            // Calcul automatique de la date de fin
            $dateDebut = Carbon::parse($request->date_debut);
            $dateFin   = $dateDebut->copy()->addDays(7);

            $designation->fill([
                'semaine_nom'         => $request->semaine_nom,
                'sous_departement_id' => $request->sous_departement_id,
                'createur_id'         => auth()->id(),
                // 2. REMPLIR LA DATE AUTOMATIQUEMENT SI ELLE MANQUE
                'date_debut'          => $request->date_debut ?? now(),
                'date_fin'            => $dateFin, // Toujours calculée à partir de la date de début
                'statut'              => $request->statut ?? 'Brouillon',
            ])->save();

            // 2. Nettoyer les anciens items si on est en modification
            if ($id) {
                $designation->items()->delete();
            }

            //dd($request->affectations);

            // 3. Parcourir les laboratoires et les rôles envoyés par React
            foreach ($request->affectations as $labId => $roles) {
                foreach ($roles as $roleItem) {
                    if (! empty($roleItem['membres'])) {
                        // Pour chaque membre sélectionné dans le MultiSelect
                        foreach ($roleItem['membres'] as $membreId) {
                            $designation->items()->create([
                                'laboratoire_id' => $labId,
                                'role_tache_id'  => $roleItem['role_id'],
                                'membre_id'      => $membreId,  // On fournit l'ID ici
                                'date_effective' => $dateDebut, // Ou une autre logique pour la date effective
                            ]);
                        }
                        // 4. Attacher les membres dans la table pivot
                        // $item->membres()->sync($roleItem['membres']);
                    }
                }
            }
        });

        return redirect()->route('designations.index');
    }
    /**
     * Suppression (Optionnel)
     */
    public function destroy($id)
    {
        $designation = Designation::findOrFail($id);
        $designation->delete(); // Les items et pivots seront supprimés si cascadeOnDelete() est mis

        return redirect()->back()->with('message', 'Désignation supprimée.');
    }

}
