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
        // On récupère les groupes avec leur pivot vers les sous-départements
        $groupes = Group::with('sousDepartements')->get()->map(function ($groupe) {
            // On formate pour faciliter la lecture dans React : [id_sous_dept => niveau_acces]
            $pivotData = [];
            foreach ($groupe->sousDepartements as $sd) {
                $pivotData[$sd->id] = $sd->pivot->niveau_acces;
            }
            
            return [
                'id' => $groupe->id,
                'nom' => $groupe->name,
                'permissions' => $pivotData
            ];
        });

        return Inertia::render('Admin/GroupPermissions', [
            'matrixData' => $groupes,
            'sousDepartements' => SousDepartement::select('id', 'nom')->get()
        ]);
    }

    public function updatePivot(Request $request)
    {
        $request->validate([
            'groupe_id' => 'required|exists:groupes,id',
            'sous_departement_id' => 'required|exists:sous_departements,id',
            'niveau_acces' => 'required|string' // 'aucune', 'lecture', 'ecriture', 'total'
        ]);

        $groupe = Group::findOrFail($request->groupe_id);
        $sdId = $request->sous_departement_id;
        $niveau = $request->niveau_acces;

        if ($niveau === 'aucune') {
            // Si l'accès est "aucune", on détruit la ligne dans le pivot
            $groupe->sousDepartements()->detach($sdId);
        } else {
            // Sinon, on crée ou met à jour la colonne 'niveau_acces'
            $groupe->sousDepartements()->syncWithoutDetaching([
                $sdId => ['niveau_acces' => $niveau]
            ]);
        }

        return redirect()->back()->with('success', 'Droits mis à jour avec succès.');
    }
}