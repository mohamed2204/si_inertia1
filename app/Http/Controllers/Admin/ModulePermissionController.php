<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ModulePermissionController extends Controller
{
    public function index()
    {
        // 1. Définir la liste des modules globaux de votre application
        $availableModules = [
            ['code' => 'designations', 'name' => 'Gestion des Désignations'],
            ['code' => 'users', 'name' => 'Gestion des Utilisateurs'],
            ['code' => 'factures', 'name' => 'Gestion de la Facturation'],
        ];

        // 2. Récupérer les groupes avec leurs permissions polymorphes existantes
        $groupes = Group::all()->map(function ($group) {
            // Récupérer toutes les lignes de 'permissions_groupes' pour ce groupe
            $permissions = DB::table('permissions_groupes')
                ->where('group_id', $group->id)
                ->get()
                ->map(function ($p) {
                    return [
                        'action' => $p->type_action,
                        'module' => $p->module_type, // ex: 'designations'
                    ];
                });

            return [
                'id' => $group->id,
                'name' => $group->name,
                'code' => $group->code,
                'permissions' => $permissions
            ];
        });

        return Inertia::render('Admin/ModulePermissions', [
            'groupes' => $groupes,
            'modules' => $availableModules
        ]);
    }

    public function togglePermission(Request $request)
    {
        $validated = $request->validate([
            'group_id'    => 'required|exists:groups,id',
            'module_type' => 'required|string', // ex: 'designations'
            'type_action' => 'required|string|in:lecture,modification,suppression',
        ]);

        // Chercher si la permission polymorphe existe déjà
        $existing = DB::table('permissions_groupes')
            ->where('group_id', $validated['group_id'])
            ->where('module_type', $validated['module_type'])
            ->where('type_action', $validated['type_action'])
            ->first();

        if ($existing) {
            // Si elle existe, l'utilisateur a décoché le bouton -> On supprime
            DB::table('permissions_groupes')->where('id', $existing->id)->delete();
        } else {
            // Si elle n'existe pas, l'utilisateur a coché le bouton -> On l'ajoute
            DB::table('permissions_groupes')->insert([
                'group_id' => $validated['group_id'],
                'type_action' => $validated['type_action'],
                'module_type' => $validated['module_type'],
                'module_id' => null, // null car cela s'applique à TOUT le module globalement
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->back();
    }
}