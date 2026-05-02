<?php
namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\Laboratoire;
use App\Models\LabRequis;

/**
 * @cite 1: L'utilisateur utilise PostgreSQL pour son projet school-management2.
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LabRequisController extends Controller
{

    /**
     * Retourne la liste complète des requis disponibles.
     * Cette fonction est appelée par votre route 'api.requis.list'.
     */
    public function list()
    {
        // On récupère uniquement l'ID et le Nom pour optimiser la charge JSON sur PostgreSQL
        return response()->json(
            LabRequis::select('id', 'libelle')
                ->orderBy('libelle', 'asc')
                ->get()
        );
    }

    public function index()
    {
        $user    = auth()->user();
        $isAdmin = $user->hasRole('admin');

        if ($isAdmin) {
            // L'admin voit tout l'arbre
            $structure = Departement::with([
                'sousDepartements.laboratoires.labRequis.roleTache',
            ])->get();
        } else {
            // L'utilisateur voit seulement les départements liés à ses groupes
            $groupIds = $user->groups->pluck('id');

            $structure = Departement::whereHas('sousDepartements.groups', function ($q) use ($groupIds) {
                $q->whereIn('groups.id', $groupIds);
            })
                ->with(['sousDepartements' => function ($q) use ($groupIds) {
                    $q->whereHas('groups', fn($g) => $g->whereIn('groups.id', $groupIds))
                        ->with('laboratoires.labRequis.roleTache');
                }])
                ->get();
        }

        return Inertia::render('Admin/Laboratoires/RequisConfig', [
            'structure'        => $structure,
            'allRequisOptions' => DB::table('role_taches')->select('id', 'libelle as nom')->get(),
            'sectionTypes'     => [
                ['label' => 'Jour', 'value' => 'jour'],
                // ['label' => 'Garde', 'value' => 'garde'],
                ['label' => 'Responsable', 'value' => 'responsable'],
                // ['label' => 'Nuit', 'value' => 'nuit'],
            ],
        ]);
    }

    /**
     * Synchronise les requis d'un laboratoire (méthode appelée par le Repeater).
     */
    public function sync(Request $request, Laboratoire $laboratoire)
    {
        // Validation : Le labo appartient-il à un sous-dept autorisé pour l'utilisateur ?

        //dd($request->requis_list);

        // 1. Vérification prioritaire : si l'utilisateur est admin, on ignore la suite
        if (auth()->user()->hasRole('admin')) {
            // L'admin passe directement
        } else {
            // 2. Pour les autres, on vérifie l'appartenance au groupe
            $userGroup = auth()->user()->groups()->first();

            if (! $userGroup) {
                return back()->with('error', 'Vous n\'appartenez à aucun groupe autorisé.');
            }

            $isAuthorized = DB::table('group_sous_departement')
                ->where('group_id', $userGroup->id)
                ->where('sous_departement_id', $laboratoire->sous_departement_id)
                ->exists();

            if (! $isAuthorized) {
                return back()->with('error', 'Accès non autorisé à ce laboratoire.');
            }
        }

        $request->validate([
            'requis_list'                 => 'array',
            'requis_list.*.role_tache_id' => 'required|exists:role_taches,id',
            'requis_list.*.nombre_requis' => 'numeric|min:1',
            'requis_list.*.section'       => 'string',
        ]);

        try {
            DB::beginTransaction();

            // Supprimer les anciens requis pour ce labo
            LabRequis::where('laboratoire_id', $laboratoire->id)->delete();

            // Insertion avec les colonnes de votre schéma (role_tache_id, nombre_requis, section, ordre)
            $insertData = collect($request->requis_list)->map(function ($item, $index) use ($laboratoire) {
                return [
                    'laboratoire_id' => $laboratoire->id,
                    'role_tache_id'  => $item['role_tache_id'],
                    'nombre_requis'  => $item['nombre_requis'] ?? 1,
                    'section'        => $item['section'] ?? 'Principale',
                    'ordre'          => $index,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
            })->toArray();

            LabRequis::insert($insertData);

            DB::commit();
            return back()->with('success', 'Configuration mise à jour.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }
}
//     public function list()
//     {
//         return response()->json(DB::table('role_taches')->select('id', 'nom')->get());
//     }
// }

// class RequisController extends Controller
// {
//     /**
//      * Synchronise les requis d'un laboratoire avec gestion de l'ordre.
//      */
//     public function sync(Request $request, Laboratoire $laboratoire)
//     {
//         $request->validate([
//             'requis_list' => 'array',
//             'requis_list.*.requis_id' => 'required|exists:requis,id',
//         ]);

//         try {
//             DB::beginTransaction();

//             // 1. Supprimer les anciens requis pour ce laboratoire
//             // (Ou utiliser sync() si c'est une relation BelongsToMany standard)
//             LabRequis::where('laboratoire_id', $laboratoire->id)->delete();

//             // 2. Insérer les nouveaux requis avec l'ordre défini par le Drag & Drop
//             $dataToInsert = collect($request->requis_list)->map(function ($item, $index) use ($laboratoire) {
//                 return [
//                     'laboratoire_id' => $laboratoire->id,
//                     'requis_id'      => $item['requis_id'],
//                     'ordre'          => $index, // L'index du tableau React devient la position SQL
//                     'created_at'     => now(),
//                     'updated_at'     => now(),
//                 ];
//             })->toArray();

//             LabRequis::insert($dataToInsert);

//             DB::commit();

//             return back()->with('success', 'Configuration des requis mise à jour avec succès.');

//         } catch (\Exception $e) {
//             DB::rollBack();
//             return back()->with('error', 'Erreur lors de la sauvegarde : ' . $e->getMessage());
//         }
//     }

//     /**
//      * Retourne la liste des requis pour le peuplement du Dropdown.
//      */
//     public function list()
//     {
//         return RoleTache::select('id', 'libelle')->orderBy('libelle')->get();
//     }
// }
