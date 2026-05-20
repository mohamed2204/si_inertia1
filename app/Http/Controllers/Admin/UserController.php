<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Approche Hybride : Rendu de la vue ou réponse JSON pour Axios
     */
    public function index(Request $request): Response|JsonResponse
    {
        $currentUser = $request->user()->load('groups');
        //dd($currentUser); // Debug pour vérifier les données de l'utilisateur connecté

        // Sécurité : Seuls la Direction ou les Administrateurs gèrent les comptes utilisateurs
        if (!$currentUser->hasAbsoluteView()) {
            abort(403, "Vous n'avez pas les droits nécessaires pour gérer les utilisateurs.");
        }

        // Si c'est un appel API Axios (Demande de données)
        if ($request->wantsJson()) {
            $query = User::with('groups');

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

            // Tri et pagination
            $paginatedUsers = $query->orderBy(
                $request->input('sort_by') ?? 'name',
                $request->input('sort_dir') ?? 'asc'
            )->paginate($request->input('per_page') ?? 10);

            // Injection des permissions d'action par ligne pour le tableau React
            $paginatedUsers->through(function ($user) {
                // Un utilisateur ne peut pas se supprimer lui-même
                $user->can_edit = true;
                $user->can_delete = auth()->id() !== $user->id;
                return $user;
            });

            return response()->json($paginatedUsers);
        }

        // Premier chargement de la page (Inertia skeleton)
        return Inertia::render('Users/Index', [
            'results'       => null, // Axios chargera les données au premier rendu
            'all_groups'    => Group::orderBy('name')->get(['id', 'name', 'code']),
            'can_create'    => true
        ]);
    }

    /**
     * Enregistrer un nouvel utilisateur
     */
    public function store(Request $request): JsonResponse
    {
        if (!$request->user()->hasAbsoluteView()) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['exists:groups,id'],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        if (!empty($validated['group_ids'])) {
            $user->groups()->sync($validated['group_ids']);
        }

        return response()->json(['message' => 'Utilisateur créé avec succès', 'user' => $user], 201);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, User $user): JsonResponse
    {
        if (!$request->user()->hasAbsoluteView()) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password'  => ['nullable', 'string', 'min:8'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['exists:groups,id'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
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
        if (!$request->user()->hasAbsoluteView() || $request->user()->id === $user->id) {
            return response()->json(['message' => 'Action non autorisée ou impossible'], 403);
        }

        // Détachement automatique des relations de groupe avant suppression
        $user->groups()->detach();
        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé avec succès']);
    }
}