<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\SousDepartement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PermissionController extends Controller
{
    public function index()
    {
        // =========================================================================
        // --- DONNÉES ONGLET 1 : MODULES GLOBAUX (PAR GROUPE) ---
        // =========================================================================

        // 1. Liste des modules globaux depuis la configuration
        $availableModules = config('modules.list');

        // 2. Récupération de tous les groupes
        $groupes = Group::all();

        // 3. Permissions globales des modules par groupe
        $modulePermissions = DB::table('permissions_groupes')
            ->select('group_id', 'module_type', 'type_action')
            ->get();

        // =========================================================================
        // --- DONNÉES COMMUNES & ONGLET 2 : TERRAINS (PAR UTILISATEUR) ---
        // =========================================================================

        // 4. Récupération des sous-départements formatés et triés de A à Z (Votre logique optimisée)
        $sousDepartements = SousDepartement::with('departement')
            ->get()
            ->map(function ($sd) {
                $displayName = $sd->departement
                    ? "{$sd->departement->nom} - {$sd->nom}"
                    : $sd->nom;

                return [
                    'id'  => $sd->id,
                    'nom' => $displayName,
                ];
            })
            ->sortBy('nom')
            ->values();

        // 5. NOUVEAU : Chargement des utilisateurs (non-admins) avec leurs groupes rattachés
        // On charge explicitement 'groupes' (ou 'groups' selon votre modèle)
        $utilisateurs = User::where('is_admin', false)
            ->with('groups:id,name') // 👈 Ajustez en 'groups' si votre méthode dans User.php est en anglais
            ->get(['id', 'name', 'email']);

        // 6. NOUVEAU : Récupération de la table pivot CRUD pour les utilisateurs
        $affectations = DB::table('sous_departement_user')
            ->select('user_id', 'sous_departement_id', 'can_create', 'can_read', 'can_update', 'can_delete')
            ->get();

        // =========================================================================
        // --- ENVOI DES DONNÉES À INERTIA REACT ---
        // =========================================================================
        return Inertia::render('Admin/AllPermissions', [
            // Props pour l'onglet 1
            'groupes'           => $groupes,
            'modules'           => $availableModules,
            'modulePermissions' => $modulePermissions,

            // Props communes et pour l'onglet 2
            'sousDepartements'  => $sousDepartements,
            'utilisateurs'      => $utilisateurs, // 👈 Ajouté pour le tableau et le filtre
            'affectations'      => $affectations, // 👈 Ajouté à la place de pivotPermissions
        ]);
    }


    /**
     * Alterne (Ajoute ou Supprime) une permission polymorphe globale pour un module.
     */
    public function toggleModulePermission(Request $request)
    {
        // 1. Validation stricte des données entrantes depuis la page React
        $validated = $request->validate([
            'group_id'    => 'required|exists:groups,id',
            'module_type' => 'required|string',
            'type_action' => 'required|string|in:lecture,modification,suppression',
        ]);

        // 2. Recherche si la permission existe déjà en base de données
        $existing = DB::table('permissions_groupes')
            ->where('group_id', $validated['group_id'])
            ->where('module_type', $validated['module_type'])
            ->where('type_action', $validated['type_action'])
            ->first();

        if ($existing) {
            // Si elle existe, l'utilisateur a décoché la case -> On retire le droit
            DB::table('permissions_groupes')->where('id', $existing->id)->delete();
        } else {
            // Si elle n'existe pas, l'utilisateur a coché la case -> On accorde le droit
            DB::table('permissions_groupes')->insert([
                'group_id'    => $validated['group_id'],
                'module_type' => $validated['module_type'],
                'type_action' => $validated['type_action'],
                'module_id'   => null, // Reste à null car cela couvre l'intégralité du module global
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // 3. Retourne sur la même vue en rafraîchissant les states d'Inertia
        return redirect()->back();
    }

    /**
     * Alterne les permissions CRUD sur les Sous-Départements pour un utilisateur spécifique.
     */
    public function togglePermission(Request $request)
    {
        $validated = $request->validate([
            'user_id'             => 'required|exists:users,id',
            'sous_departement_id' => 'required|exists:sous_departements,id',
            'permission'          => 'required|in:can_create,can_read,can_update,can_delete',
            'value'               => 'required|boolean',
        ]);

        $userId = $validated['user_id'];
        $sdId   = $validated['sous_departement_id'];
        $column = $validated['permission'];
        $value  = $validated['value'];

        // Utilisation de updateOrInsert pour créer la ligne si elle n'existe pas encore
        DB::table('sous_departement_user')->updateOrInsert(
            [
                'user_id'             => $userId,
                'sous_departement_id' => $sdId,
            ],
            [
                $column      => $value,
                'updated_at' => now(),
            ]
        );

        // Si la valeur passe à faux, on vérifie si on doit nettoyer la ligne devenue inutile
        if (! $value) {
            $row = DB::table('sous_departement_user')
                ->where('user_id', $userId)
                ->where('sous_departement_id', $sdId)
                ->first();

            if ($row && ! $row->can_create && ! $row->can_read && ! $row->can_update && ! $row->can_delete) {
                DB::table('sous_departement_user')
                    ->where('user_id', $userId)
                    ->where('sous_departement_id', $sdId)
                    ->delete();
            }
        }

        return redirect()->back();
    }

    /**
     * Met à jour ou supprime l'accès pivot d'un groupe à un sous-département/labo.
     */
    public function updatePivotPermission(Request $request)
    {
        // 1. Validation stricte des données envoyées par les RadioButtons React
        $validated = $request->validate([
            'groupe_id'           => 'required|exists:groups,id',
            'sous_departement_id' => 'required|exists:sous_departements,id',
            'niveau_acces'        => 'required|in:aucune,lecture,ecriture,total',
        ]);

        // 2. Récupérer l'instance du groupe concerné
        $group = Group::findOrFail($validated['groupe_id']);

        // 3. Traitement selon la valeur sélectionnée
        if ($validated['niveau_acces'] === 'aucune') {
            // Si l'admin coche "Aucun", on supprime complètement la ligne dans la table pivot
            $group->sousDepartements()->detach($validated['sous_departement_id']);
        } else {
            // Sinon, on insère ou on met à jour la colonne 'niveau_acces' sans toucher aux autres liaisons
            $group->sousDepartements()->syncWithoutDetaching([
                $validated['sous_departement_id'] => [
                    'niveau_acces' => $validated['niveau_acces'],
                ],
            ]);
        }

        // 4. Rafraîchissement transparent des données côté React via Inertia
        return redirect()->back();
    }
}
