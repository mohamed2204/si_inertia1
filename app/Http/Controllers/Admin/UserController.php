<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\SousDepartement;
use App\Models\User; // Ajout du modèle
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Approche Hybride : Rendu de la vue ou réponse JSON pour Axios
     */
    public function index(Request $request): Response | JsonResponse
    {
        $currentUser = $request->user()->load('groups');

        // Sécurité : Seuls la Direction ou les Administrateurs gèrent les comptes utilisateurs
        if (! $currentUser->hasAbsoluteView()) {
            abort(403, "Vous n'avez pas les droits nécessaires pour gérer les utilisateurs.");
        }

        // Si c'est un appel API Axios (Demande de données)
        if ($request->wantsJson()) {
            // Eager load de 'groups' ET de la relation d'appartenance principale 'sousDepartement'
            $query = User::with(['groups', 'sousDepartement']);

            // Recherche textuelle
            $query->when($request->input('search'), function ($q, $search) {
                $q->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            });

            // Filtre optionnel par groupe
            $query->when($request->input('group_id'), function ($q, $groupId) {
                $q->whereHas('groups', function ($sq) use ($groupId) {
                    $sq->where('groups.id', $groupId);
                });
            });

            // NOUVEAU : Filtre optionnel par sous-département d'appartenance
            $query->when($request->input('sous_departement_id'), function ($q, $sdId) {
                $q->where('sous_departement_id', $sdId);
            });

            // Tri et pagination
            $paginatedUsers = $query->orderBy(
                $request->input('sort_by') ?? 'name',
                $request->input('sort_dir') ?? 'asc'
            )->paginate($request->input('per_page') ?? 10);

            // Injection des permissions d'action par ligne pour le tableau React
            $paginatedUsers->through(function ($user) {
                $user->can_edit   = true;
                $user->can_delete = auth()->id() !== $user->id;
                return $user;
            });

            return response()->json($paginatedUsers);
        }

        // Premier chargement de la page (Inertia skeleton)
        return Inertia::render('Users/Index', [
            'results'        => null,
            'all_groups'     => Group::orderBy('name')->get(['id', 'name', 'code']),
            // NOUVEAU : Passage des sous-départements pour remplir le Select du formulaire ou des filtres
            // Remplacer l'ancienne ligne par celle-ci dans la méthode index()
            'all_sous_depts' => SousDepartement::with('departement')
                ->get()
                ->map(function ($sd) {
                    return [
                        'id'  => $sd->id,
                        'nom' => $sd->nom_complet, // Utilisation de l'accesseur virtuel
                    ];
                })
                ->sortBy('nom')
                ->values(),
            'can_create'     => true,
        ]);
    }

    /**
     * Enregistrer un nouvel utilisateur
     */
    public function store(Request $request): JsonResponse
    {
        if (! $request->user()->hasAbsoluteView()) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'            => ['required', 'string', 'min:8'],
            'sous_departement_id' => ['nullable', 'exists:sous_departements,id'], // Validation de l'appartenance
            'group_ids'           => ['nullable', 'array'],
            'group_ids.*'         => ['exists:groups,id'],
        ]);

        $user = User::create([
            'name'                => $validated['name'],
            'email'               => $validated['email'],
            'password'            => Hash::make($validated['password']),
            'sous_departement_id' => $validated['sous_departement_id'] ?? null, // Assignation directe
            'is_admin'           => false, // Par défaut, un nouvel utilisateur n'est pas admin
        ]);

        if (! empty($validated['group_ids'])) {
            $user->groups()->sync($validated['group_ids']);
        }

        // Optionnel : recharger la relation avant de renvoyer la réponse au frontend
        $user->load('sousDepartement');

        return response()->json(['message' => 'Utilisateur créé avec succès', 'user' => $user], 201);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, User $user): JsonResponse
    {
        if (! $request->user()->hasAbsoluteView()) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password'            => ['nullable', 'string', 'min:8'],
            'sous_departement_id' => ['nullable', 'exists:sous_departements,id'], // Validation de l'appartenance
            'group_ids'           => ['nullable', 'array'],
            'group_ids.*'         => ['exists:groups,id'],
        ]);

        $user->name                = $validated['name'];
        $user->email               = $validated['email'];
        $user->sous_departement_id = $validated['sous_departement_id'] ?? null; // Mise à jour de l'appartenance

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // Synchronisation des groupes d'accès rattachés
        $group_ids = $validated['group_ids'] ?? [];
        $user->groups()->sync($group_ids);

        return response()->json(['message' => 'Utilisateur mis à jour avec succès']);
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if (! $request->user()->hasAbsoluteView() || $request->user()->id === $user->id) {
            return response()->json(['message' => 'Action non autorisée ou impossible'], 403);
        }

        // Nettoyage de la table pivot de droits avant suppression
        $user->groups()->detach();

        // NOUVEAU : Nettoyage de la table pivot de la matrice terrain si nécessaire
        if (method_exists($user, 'sousDepartements')) {
            $user->sousDepartements()->detach();
        }

        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé avec succès']);
    }
}
