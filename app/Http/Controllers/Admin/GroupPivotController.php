<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\SousDepartement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GroupPivotController extends Controller
{
    public function index()
    {
        // On vérifie si l'utilisateur a le droit de voir les permissions des groupes
        // $this->authorize('viewAny', Group::class);

        // 1. On récupère tous les sous-départements avec leurs liaisons groupes
        $sousDepartements = SousDepartement::with(['groups' => function ($query) {
            // Sécurité : On force Laravel à charger la colonne pivot
            $query->withPivot('niveau_acces');
        }])->get()->map(function ($sd) {

            $pivotData = [];

            foreach ($sd->groups as $group) {
                // Grâce au withPivot ci-dessus, cette ligne fonctionne à 100%
                $pivotData[$group->id] = $group->pivot->niveau_acces ?? 'aucune';
            }

            return [
                'id'          => $sd->id,
                // ATTENTION : Vérifiez si votre colonne s'appelle 'nom' ou 'name' en BDD
                'nom'         => $sd->nom ?? $sd->name,
                'permissions' => $pivotData, // [groupe_id => niveau_acces]
            ];
        });

        // Optionnel : Retirez le dd() pour que l'affichage Inertia se fasse côté React
        // dd($sousDepartements->toArray());

        return Inertia::render('Admin/GroupPermissions', [
            // Utiliser ->values() garantit à Inertia de recevoir un vrai tableau JSON [], pas un objet {}
            'matrixData' => $sousDepartements->values(),
            'groupes'    => Group::select('id', 'name')->get(), // Les colonnes de votre matrice
        ]);
    }
    // public function index()
    // {

    //     // On vérifie si l'utilisateur a le droit de voir les permissions des groupes
    //     //$this->authorize('viewAny', Group::class);

    //    // 1. On récupère tous les sous-départements avec leurs liaisons groupes
    //     $sousDepartements = SousDepartement::with('groups')->get()->map(function ($sd) {
    //         $pivotData = [];
    //         //dd($sd->groups); // Debug : voir les groupes liés à ce sous-département
    //         foreach ($sd->groups as $group) {
    //             // Désormais, cette ligne ne sera plus null !
    //         $pivotData[$group->id] = $group->pivot->niveau_acces ?? 'aucune';
    //         }

    //         return [
    //             'id' => $sd->id,
    //             'nom' => $sd->nom,
    //             'permissions' => $pivotData // [groupe_id => niveau_acces]
    //         ];
    //     });

    //     dd($sousDepartements->toArray()); // Debug : voir les données préparées pour la matrice

    //     return Inertia::render('Admin/GroupPermissions', [
    //         'matrixData' => $sousDepartements, // Maintenant ce sont les sous-départements
    //         'groupes' => Group::select('id', 'name')->get() // Les colonnes
    //     ]);
    // }

    public function updatePivot(Request $request)
    {
        // 1. Validation stricte des données reçues depuis React
        $validated = $request->validate([
            'groupe_id'           => 'required|exists:groups,id',
            'sous_departement_id' => 'required|exists:sous_departements,id',
            'niveau_acces'        => 'required|in:aucune,lecture,ecriture,total',
        ]);

        // 2. Récupération du Groupe concerné
        $group = Group::findOrFail($validated['groupe_id']);

        // 3. Traitement de l'affectation
        if ($validated['niveau_acces'] === 'aucune') {
            // Si l'utilisateur a désactivé le bouton ou choisi "Aucun", on retire l'accès
            $group->sousDepartements()->detach($validated['sous_departement_id']);
        } else {
            // Sinon, on crée ou on met à jour la ligne pivot avec la valeur correspondante
            $group->sousDepartements()->syncWithoutDetaching([
                $validated['sous_departement_id'] => [
                    'niveau_acces' => $validated['niveau_acces'],
                ],
            ]);
        }

        // 4. Retour vers le composant React
        // Grâce au fonctionnement d'Inertia, faire un back() va recharger automatiquement
        // les propriétés rafraîchies (matrixData) sans recharger complètement la page du navigateur.
        return redirect()->back();
    }
}
