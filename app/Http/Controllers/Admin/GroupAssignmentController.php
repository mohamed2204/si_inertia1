<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GroupAssignmentController extends Controller
{

    // tODO: Ajouter une validation plus stricte pour les niveaux d'accès dans le pivot (lecture, écriture, total)
    // use Illuminate\Validation\Rule;

    // $request->validate([
    //     'niveau_acces' => [
    //         'required',
    //         Rule::in(['lecture', 'ecriture', 'total']), // On bloque les valeurs autorisées ici !
    //     ],
    // ]);
    public function index()
    {
        return Inertia::render('Admin/GroupAssignments', [
            // On récupère les utilisateurs avec leurs groupes actuels
            'users'  => User::with('groups:id,name')->select('id', 'name', 'email')->get(),
            // Tous les groupes disponibles pour les dropdowns / multi-sélections
            'groups' => Group::select('id', 'name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'group_ids'   => 'array',
            'group_ids.*' => 'exists:groups,id',
        ]);

        // Synchro magique : supprime les anciens groupes et met les nouveaux
        $user->groups()->sync($request->input('group_ids', []));

        return redirect()->back()->with('success', 'Groupes mis à jour pour ' . $user->name);
    }
}
