<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SousDepartement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SousDepartementUserController extends Controller
{
    /**
     * Afficher la matrice de gestion des droits terrains
     */
    public function index()
    {
        // 1. Récupérer les utilisateurs avec leurs rôles/groupes (via Spatie ou votre système)
        // On exclut les super-admins pour ne pas surcharger la matrice inutilement
        $utilisateurs = User::where('is_admin', false)->with('groups')->get();

        if ($utilisateurs->isEmpty()) {
            dd("Attention : Aucun utilisateur n'a été trouvé avec 'is_admin = false' dans la base de données !");
        }



        //dd($utilisateurs);
        // 2. Récupérer tous les sous-départements
        $sousDepartements = SousDepartement::get(['id', 'nom']);

        // 3. Récupérer toutes les affectations actuelles avec le détail CRUD
        $affectations = DB::table('sous_departement_user')->get([
            'user_id',
            'sous_departement_id',
            'can_create',
            'can_read',
            'can_update',
            'can_delete',
        ]);

        return Inertia::render('Admin/Security/TerrainPermissions', [
            'utilisateurs'     => $utilisateurs,
            'sousDepartements' => $sousDepartements,
            'affectations'     => $affectations,
        ]);
    }

    /**
     * Mettre à jour un droit CRUD spécifique pour un utilisateur sur un sous-département
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

        // Optionnel : Si tous les droits CRUD deviennent faux, on peut nettoyer la table
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
}
