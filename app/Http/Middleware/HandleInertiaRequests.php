<?php
namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // return [
        //     ...parent::share($request),
        //     //
        // ];

        $allowedModules = [];
        $user           = $request->user();
        if ($user) {
            if ($user->is_admin) {
                // L'admin a accès à tous les modules théoriques (ou vous pouvez laisser un tableau vide et gérer le passe-droit côté Front)
                $allowedModules = ['all'];
            } else {
                // 1. On récupère les IDs des groupes de l'utilisateur
                $groupIds = $user->groups()->pluck('groups.id')->toArray();

                // 2. On récupère la liste unique des modules autorisés pour ces groupes
                $allowedModules = DB::table('permissions_groupes')
                    ->whereIn('group_id', $groupIds)
                    ->where('type_action', 'lecture') // On cible uniquement ceux qui ont le droit de vue
                    ->pluck('module_type')            // On extrait la colonne (ex: ['designations', 'users'])
                    ->unique()
                    ->toArray();

                                                                               // 2. OPTIONNEL : Si vous voulez donner l'accès à la matrice terrain
                                                                               // automatiquement à certains groupes globaux (ex: le groupe 'Chef' ou 'Super_Admin')
                $userRoles = $user->groups()->pluck('groups.code')->toArray(); // ou 'name'

                // if (in_array('Chef', $userRoles) || $user->hasPermissionTo('manage_matrix')) {
                //     $allowedModules[] = 'permissions.terrain';
                // }
                // dd($allowedModules); // Debug pour vérifier les modules autorisés pour l'utilisateur connecté
            }
        }

        return array_merge(parent::share($request), [
            'auth'  => [
                'user' => $request->user() ? [
                    'id'              => $request->user()->id,
                    'name'            => $request->user()->name,
                    'email'           => $request->user()->email,
                    'is_admin'        => $request->user()->is_admin, // Si vous avez cette colonne

                    // On charge explicitement les groupes avec uniquement les champs nécessaires
                    'groups'          => $request->user()->groups()->get(['groups.id', 'groups.name', 'groups.code']),

                    // Si vous utilisez Spatie pour les rôles, chargez-les aussi ici :
                    'roles'           => $request->user()->roles()->pluck('name')->toArray(),
                    // On injecte le tableau propre des modules, ex: ['designations', 'laboratoires']
                    'allowed_modules' => array_values($allowedModules),
                ] : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
            ],
        ]);
    }
}
