<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\Designation;
use App\Models\DesignationItem;
use App\Models\Laboratoire;
use App\Models\LaboratoireConfig;
use App\Models\Membre;
use App\Models\SousDepartement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf; // 👈 N'oubliez pas d'importer la façade en haut du fichier
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DesignationPageController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();

        // 1. Sécurité de base avec la fonction fonctionnelle globale (Onglet 1)
        if (! $user->hasGlobalPermission('designations', 'lecture')) {
            abort(403, "Vous n'avez pas accès au module des désignations.");
        }

        // 2. Récupération des flags globaux de l'onglet 1 (accès au bouton/action maître)
        $hasAbsoluteView = $user->hasAbsoluteView(); // SuperAdmin ou assimilé
        $hasGlobalWrite  = $user->hasGlobalPermission('designations', 'modification');
        $hasGlobalDelete = $user->hasGlobalPermission('designations', 'suppression');

        $query = Designation::with(['sousDepartement.departement', 'createur']);

        // =========================================================================
        // SÉCURITÉ PÉRIMÉTRIQUE TERRAIN (SQL WHERE)
        // =========================================================================
        $userPermissionsMap = [];

        if (! $hasAbsoluteView) {
            // Récupère uniquement les sous-départements où l'utilisateur a explicitement le droit de lire (can_read = 1)
            $userPermissionsMap = DB::table('sous_departement_user')
                ->where('user_id', $user->id)
                ->where('can_read', true)
                ->get()
                ->keyBy('sous_departement_id')
                ->toArray();

            // Filtrage de la requête SQL
            $query->whereIn('sous_departement_id', array_keys($userPermissionsMap));
        }

        // ... (La section APPLICATION DES FILTRES reste identique chez vous) ...

        // Exécution de la pagination
        $paginatedResults = $query->orderBy(
            $request->input('sort_by') ?? 'date_debut',
            $request->input('sort_dir') ?? 'desc'
        )->paginate($request->input('per_page') ?? 10);

        // =========================================================================
        // TRANSFORMATION LIGNE PAR LIGNE (Vérification CRUD chirurgicale)
        // =========================================================================
        $paginatedResults->through(function ($designation) use ($hasAbsoluteView, $userPermissionsMap, $hasGlobalWrite, $hasGlobalDelete) {
            if ($hasAbsoluteView) {
                // Un admin possédant la vue absolue dépend uniquement des droits globaux du module
                $canEdit   = $hasGlobalWrite;
                $canDelete = $hasGlobalDelete;
            } else {
                // Récupération de la ligne d'affectation de l'utilisateur pour CE sous-département précis
                $pivot = $userPermissionsMap[$designation->sous_departement_id] ?? null;

                // Le droit d'édition final requiert le droit global ET le droit spécifique terrain (can_update)
                $canEdit = $hasGlobalWrite && $pivot && (bool) $pivot->can_update;

                // Le droit de suppression final requiert le droit global ET le droit spécifique terrain (can_delete)
                $canDelete = $hasGlobalDelete && $pivot && (bool) $pivot->can_delete;
            }

            $designation->can_edit            = $canEdit;
            $designation->can_delete          = $canDelete;
            $designation->emplacement_formate = $designation->sousDepartement && $designation->sousDepartement->departement
                ? "{$designation->sousDepartement->departement->nom} - {$designation->sousDepartement->nom}"
                : ($designation->sousDepartement->nom ?? 'Non assigné');

            return $designation;
        });

        // =========================================================================
        // CALCUL DU DROIT DE CRÉATION GLOBAL (Pour afficher/masquer le bouton "Ajouter")
        // =========================================================================
        // L'utilisateur peut créer s'il a le droit global de modification ET :
        // - Soit il a la vue absolue (SuperAdmin)
        // - Soit il possède au moins un sous-département configuré avec "can_create = 1"
        $canCreateGlobally = $hasGlobalWrite && (
            $hasAbsoluteView ||
            DB::table('sous_departement_user')
            ->where('user_id', $user->id)
            ->where('can_create', true)
            ->exists()
        );

        // =========================================================================
        // FILTRAGE DES DÉPARTEMENTS APPARENTS (Pour les filtres de l'interface)
        // =========================================================================
        if ($hasAbsoluteView) {
            // Un administrateur avec vue absolue voit tous les départements
            $departmentsForUser = Departement::orderBy('nom')->get();
        } else {
            // Un utilisateur standard ne voit que les départements des sous-départements autorisés
            $authorizedSousDepartementIds = array_keys($userPermissionsMap);

            $departmentsForUser = Departement::whereHas('sousDepartements', function ($query) use ($authorizedSousDepartementIds) {
                $query->whereIn('id', $authorizedSousDepartementIds);
            })
                ->orderBy('nom')
                ->get();
        }

        if ($request->wantsJson()) {
            return response()->json($paginatedResults);
        }

        return Inertia::render('Designations/IndexApi', [
            'results'            => null,
            'initialDepartments' => $departmentsForUser, //Departement::orderBy('nom')->get(),
            'filters'            => $request->only(['search', 'departement_id', 'sous_departement_id', 'statut']),
            'can_create'         => $canCreateGlobally,
        ]);
    }

    // {
    //     $user = $request->user();

    //     // 1. Détection adaptative des rôles d'Administration et Direction
    //     $isSuperAdmin    = $user->is_admin || $user->groups()->where('code', 'admin')->exists();
    //     $isDirection     = $user->groups()->where('name', 'Direction / Administration')->exists();
    //     $hasAbsoluteView = $isSuperAdmin || $isDirection;

    //     // 2. Récupération des IDs de tous les groupes de l'utilisateur connecté
    //     $userGroupIds = $user->groups()->pluck('groups.id')->toArray();

    //     // 3. Vérification des permissions GLOBALES au niveau du module
    //     $hasGlobalRead = $hasAbsoluteView || DB::table('permissions_groupes')
    //         ->whereIn('group_id', $userGroupIds)
    //         ->where(['module_type' => 'designations', 'type_action' => 'lecture'])
    //         ->exists();

    //     // Sécurité de base impérative : si pas de lecture globale accordée, accès interdit
    //     if (! $hasGlobalRead) {
    //         abort(403, "Vous n'avez pas accès au module des désignations.");
    //     }

    //     $hasGlobalWrite = $hasAbsoluteView || DB::table('permissions_groupes')
    //         ->whereIn('group_id', $userGroupIds)
    //         ->where(['module_type' => 'designations', 'type_action' => 'modification'])
    //         ->exists();

    //     $hasGlobalDelete = $hasAbsoluteView || DB::table('permissions_groupes')
    //         ->whereIn('group_id', $userGroupIds)
    //         ->where(['module_type' => 'designations', 'type_action' => 'suppression'])
    //         ->exists();

    //     // Préparer la requête SQL de base Eloquent
    //     $query = Designation::with(['sousDepartement.departement', 'createur']);

    //     // =========================================================================
    //     // SÉCURITÉ ET DROITS PIVOTS LOCAUX (PÉRIMÈTRE DES LABOS)
    //     // =========================================================================
    //     $userLabAccess = [];

    //     if (! $hasAbsoluteView) {
    //         // Construction du dictionnaire des accès de l'utilisateur [sous_departement_id => niveau_acces]
    //         $userLabAccess = DB::table('group_sous_departement')
    //             ->whereIn('group_id', $userGroupIds)
    //             ->get()
    //             ->groupBy('sous_departement_id')
    //             ->map(function ($items) {
    //                 // En cas de multi-groupes aux droits conflictuels, on conserve le privilège le plus élevé
    //                 $priorite = ['total' => 3, 'ecriture' => 2, 'lecture' => 1, 'aucune' => 0];
    //                 return $items->sortByDesc(fn($item) => $priorite[$item->niveau_acces] ?? 0)->first()->niveau_acces;
    //             })
    //             ->toArray();

    //         // Filtrage strict au niveau SQL : l'utilisateur ne voit que ses laboratoires assignés
    //         $allowedSousDeptIds = array_keys($userLabAccess);
    //         $query->whereIn('sous_departement_id', $allowedSousDeptIds);
    //     }

    //     // =========================================================================
    //     // APPLICATION DES FILTRES DYNAMIQUES DE RECHERCHE
    //     // =========================================================================
    //     $query->when($request->input('search'), function ($q, $search) {
    //         $q->where(function ($subQuery) use ($search) {
    //             $subQuery->where('semaine_nom', 'LIKE', "%{$search}%")
    //                 ->orWhere('notes_generales', 'LIKE', "%{$search}%");
    //         });
    //     });

    //     $query->when($request->input('departement_id'), function ($q, $deptId) {
    //         $q->whereHas('sousDepartement', function ($sq) use ($deptId) {
    //             $sq->where('departement_id', $deptId);
    //         });
    //     });

    //     $query->when($request->input('sous_departement_id'), function ($q, $sdId) {
    //         $q->where('sous_departement_id', $sdId);
    //     });

    //     $query->when($request->input('statut'), function ($q, $statut) {
    //         $q->where('statut', $statut);
    //     });

    //     // =========================================================================
    //     // EXÉCUTION DE LA PAGINATION ET DES TRIS
    //     // =========================================================================
    //     $paginatedResults = $query->orderBy(
    //         $request->input('sort_by') ?? 'date_debut',
    //         $request->input('sort_dir') ?? 'desc'
    //     )->paginate($request->input('per_page') ?? 10);

    //     // =========================================================================
    //     // TRANSFORMATION LIGNE PAR LIGNE (Injection des permissions d'action)
    //     // =========================================================================
    //     $paginatedResults->through(function ($designation) use ($hasAbsoluteView, $userLabAccess, $hasGlobalWrite, $hasGlobalDelete) {
    //         // 1. Droits pour Admin ou Direction
    //         if ($hasAbsoluteView) {
    //             $canEdit   = $hasGlobalWrite;
    //             $canDelete = $hasGlobalDelete;
    //         }
    //         // 2. Droits pour Utilisateur standard (déduits du dictionnaire des labos)
    //         else {
    //             $labLevel = $userLabAccess[$designation->sous_departement_id] ?? 'aucune';

    //             // Modifier : Droit d'écriture global requis + niveau local 'ecriture' ou 'total'
    //             $canEdit = $hasGlobalWrite && in_array($labLevel, ['ecriture', 'total']);

    //             // Supprimer : Droit de suppression global requis + niveau local 'total' uniquement
    //             $canDelete = $hasGlobalDelete && ($labLevel === 'total');
    //         }

    //         // Formatage de la chaîne d'emplacement combinée
    //         $emplacement = $designation->sousDepartement && $designation->sousDepartement->departement
    //             ? "{$designation->sousDepartement->departement->nom} - {$designation->sousDepartement->nom}"
    //             : ($designation->sousDepartement->nom ?? 'Non assigné');

    //         // Attachement des attributs virtuels consommés par React
    //         $designation->can_edit            = $canEdit;
    //         $designation->can_delete          = $canDelete;
    //         $designation->emplacement_formate = $emplacement;

    //         return $designation;
    //     });

    //     // Calcul du droit de création global (Vérifie si l'utilisateur possède au moins un labo en écriture/total)
    //     $canCreateGlobally = $hasGlobalWrite && (
    //         $hasAbsoluteView ||
    //         DB::table('group_sous_departement')
    //             ->whereIn('group_id', $userGroupIds)
    //             ->whereIn('niveau_acces', ['ecriture', 'total'])
    //             ->exists()
    //     );

    //     // =========================================================================
    //     // AIGUILLAGE INTELLIGENT DE LA RÉPONSE
    //     // =========================================================================
    //     if ($request->wantsJson()) {
    //         // Cas 1 : Requête Axios (Filtrage, Tri, Changement de page) -> Renvoi strict du flux JSON paginé
    //         return response()->json($paginatedResults);
    //     }

    //     // Cas 2 : Premier chargement de la page -> Inertia rend le composant structurel
    //     return Inertia::render('Designations/IndexApi', [
    //         'results'            => null, // Le tableau démarre vide, Axios l'alimente immédiatement après le montage
    //         'initialDepartments' => Departement::orderBy('nom')->get(),
    //         'filters'            => $request->only(['search', 'departement_id', 'sous_departement_id', 'statut']),
    //         'can_create'         => $canCreateGlobally,
    //     ]);
    // }
    // public function index(Request $request)
    // {
    //     $user = $request->user();

    //     //dd($user); // Debug : vérifier les groupes de l'utilisateur
    //     $isAdmin = $user->is_admin || $user->group?->code === 'admin';

    //     // 1. Récupérer tous les IDs des groupes de l'utilisateur connecté
    //     $userGroupIds = $user->groups()->pluck('groups.id')->toArray();

    //     // 2. Adapter la vérification des permissions GLOBALES
    //     $hasGlobalRead = $isAdmin || DB::table('permissions_groupes')
    //         ->whereIn('group_id', $userGroupIds) // On cherche si AU MOINS UN des groupes a le droit
    //         ->where(['module_type' => 'designations', 'type_action' => 'lecture'])
    //         ->exists();

    //     $hasGlobalWrite = $isAdmin || DB::table('permissions_groupes')
    //         ->whereIn('group_id', $userGroupIds) // On cherche si AU MOINS UN des groupes a le droit
    //         ->where(['module_type' => 'designations', 'type_action' => 'modification'])
    //         ->exists();

    //     $hasGlobalDelete = $isAdmin || DB::table('permissions_groupes')
    //         ->whereIn('group_id', $userGroupIds) // On cherche si AU MOINS UN des groupes a le droit
    //         ->where(['module_type' => 'designations', 'type_action' => 'suppression'])
    //         ->exists();

    //     // Sécurité de base : si pas de lecture globale, accès interdit
    //     if (! $hasGlobalRead) {
    //         abort(403, "Vous n'avez pas accès au module des désignations.");
    //     }

    //     // Préparer la requête SQL de base
    //     $query = Designation::with(['sousDepartement.departement', 'createur']);

    //     // =========================================================================
    //     // 2. SÉCURITÉ & RÉCUPÉRATION DES DROITS PIVOTS (OÙ)
    //     // =========================================================================
    //     $isSuperAdmin    = $isAdmin;
    //     $isDirection     = $user->groups()->where('code', 'admin')->exists();
    //     $hasAbsoluteView = $isSuperAdmin || $isDirection;

    //     // On récupère le dictionnaire complet [sous_departement_id => niveau_acces]
    //     // 3. Adapter la récupération du dictionnaire des labos
    //     $userLabAccess = DB::table('group_sous_departement')
    //         ->whereIn('group_id', $userGroupIds) // On fusionne les droits de tous ses groupes
    //         ->get()
    //         ->groupBy('sous_departement_id')
    //         ->map(function ($items) {
    //             // Si l'utilisateur est dans plusieurs groupes qui ont des droits différents sur le même labo,
    //             // on prend le droit le plus élevé (total > ecriture > lecture)
    //             $priorite = ['total' => 3, 'ecriture' => 2, 'lecture' => 1, 'aucune' => 0];
    //             return $items->sortByDesc(fn($item) => $priorite[$item->niveau_acces] ?? 0)->first()->niveau_acces;
    //         })
    //         ->toArray();

    //     // SÉCURITÉ : Filtrage de la requête SQL (on le place BIEN ici)
    //     if (! $isSuperAdmin && ! $isDirection) {
    //         // L'utilisateur ne voit que les sous-départements où il a un droit
    //         $allowedSousDeptIds = array_keys($userLabAccess);
    //         $query->whereIn('sous_departement_id', $allowedSousDeptIds);
    //     }

    //     // =========================================================================
    //     // 3. FILTRES EXISTANTS (Appliqués sur l'objet $query)
    //     // =========================================================================
    //     $query->when($request->input('search'), function ($q, $search) {
    //         $q->where(function ($subQuery) use ($search) {
    //             $subQuery->where('semaine_nom', 'LIKE', "%{$search}%")
    //                 ->orWhere('notes_generales', 'LIKE', "%{$search}%");
    //         });
    //     });

    //     $query->when($request->input('departement_id'), function ($q, $deptId) {
    //         $q->whereHas('sousDepartement', function ($sq) use ($deptId) {
    //             $sq->where('departement_id', $deptId);
    //         });
    //     });

    //     $query->when($request->input('sous_departement_id'), function ($q, $sdId) {
    //         $q->where('sous_departement_id', $sdId);
    //     });

    //     $query->when($request->input('statut'), function ($q, $statut) {
    //         $q->where('statut', $statut);
    //     });

    //     // =========================================================================
    //     // 4. EXÉCUTION DE LA PAGINATION (Création officielle de la variable !)
    //     // =========================================================================
    //     $paginatedResults = $query->orderBy(
    //         $request->input('sort_by') ?? 'created_at',
    //         $request->input('sort_dir') ?? 'desc'
    //     )->paginate($request->input('per_page') ?? 10);

    //     // =========================================================================
    //     // 5. TRANSFORMATION DES RÉSULTATS (On applique le mapping APRÈS la pagination)
    //     // =========================================================================
    //     $paginatedResults->through(function ($designation) use ($hasAbsoluteView, $userLabAccess, $hasGlobalWrite, $hasGlobalDelete) {

    //         // 1. CAS ADMIN / DIRECTION
    //         if ($hasAbsoluteView) {
    //             $canEdit   = $hasGlobalWrite;
    //             $canDelete = $hasGlobalDelete;
    //         }
    //         // 2. CAS UTILISATEUR NORMAL
    //         else {
    //             $labLevel = $userLabAccess[$designation->sous_departement_id] ?? 'aucune';

    //             // Écriture / Modification
    //             $canEdit = $hasGlobalWrite && in_array($labLevel, ['ecriture', 'total']);

    //             // Suppression
    //             $canDelete = $hasGlobalDelete && ($labLevel === 'total');
    //         }

    //         // Formatage de l'emplacement (Dep - Sous-Dept)
    //         $emplacement = $designation->sousDepartement && $designation->sousDepartement->departement
    //             ? "{$designation->sousDepartement->departement->nom} - {$designation->sousDepartement->nom}"
    //             : ($designation->sousDepartement->nom ?? 'Non assigné');

    //         // Injection des propriétés pour le front React
    //         $designation->can_edit            = $canEdit;
    //         $designation->can_delete          = $canDelete;
    //         $designation->emplacement_formate = $emplacement;

    //         return $designation;
    //     });

    //     // Vérifier si l'utilisateur a le droit de créer au moins dans UN labo
    //     $canCreateGlobally = $hasGlobalWrite && ($isSuperAdmin || $isDirection || DB::table('group_sous_departement')->where('group_id', $user->group_id)->whereIn('niveau_acces', ['ecriture', 'total'])->exists());

    //     // 6. Renvoi propre à Inertia pour React
    //     return Inertia::render('Designations/IndexApi', [
    //         'results'            => null, // Plus besoin de charger les données ici ! -------- $paginatedResults,                  // Vos désignations avec can_edit, can_delete, etc.
    //         'initialDepartments' => Departement::orderBy('nom')->get(), // Ajouté pour alimenter votre filtre de recherche
    //         'filters'            => $request->only(['search', 'departement_id', 'sous_departement_id', 'statut']),
    //         'can_create'         => $canCreateGlobally,
    //     ]);
    // }

    // public function listApi(Request $request)
    // {
    //     $user = $request->user();

    //     //dd($user->name, $user->groups()->pluck('name')); // Debug : vérifier les groupes de l'utilisateur
    //     $query = Designation::with(['sousDepartement.departement', 'createur']);

    //     // ==========================================
    //     // SÉCURITÉ : Filtrage strict par Groupe / Sous-Département
    //     // ==========================================
    //     // Si ce n'est PAS un administrateur global (Direction)
    //     if (! $user->groups()->where('name', 'Direction / Administration')->exists()) {

    //         // On récupère les sous-départements autorisés pour cet utilisateur

    //         $allowedSousDeptIds = $user->groups()
    //             ->join('group_sous_departement', 'groups.id', '=', 'group_sous_departement.group_id')
    //             ->pluck('group_sous_departement.sous_departement_id')
    //             ->toArray();

    //         // On bloque immédiatement la requête sur ses périmètres autorisés
    //         $query->whereIn('sous_departement_id', $allowedSousDeptIds);
    //     }

    //     //dd($query->toSql(), $query->getBindings()); // Debug : voir la requête générée et les paramètres

    //     // ==========================================
    //     // FILTRES EXISTANTS (Modifiés pour la sécurité)
    //     // ==========================================

    //     // 1. Recherche textuelle (Encapsulée dans une fonction pour ne pas casser le whereIn de sécurité)
    //     $query->when($request->input('search'), function ($q, $search) {
    //         $q->where(function ($subQuery) use ($search) {
    //             $subQuery->where('semaine_nom', 'LIKE', "%{$search}%")
    //                 ->orWhere('notes_generales', 'LIKE', "%{$search}%");
    //         });
    //     });

    //     // 2. Filtre par Département
    //     $query->when($request->input('departement_id'), function ($q, $deptId) {
    //         $q->whereHas('sousDepartement', function ($sq) use ($deptId) {
    //             $sq->where('departement_id', $deptId);
    //         });
    //     });

    //     // 3. Filtre par Sous-Département
    //     $query->when($request->input('sous_departement_id'), function ($q, $sdId) {
    //         $q->where('sous_departement_id', $sdId);
    //     });

    //     // 4. Filtre par Statut
    //     $query->when($request->input('statut'), function ($q, $statut) {
    //         $q->where('statut', $statut);
    //     });

    //     // 5. Tri et Pagination
    //     $results = $query->orderBy(
    //         $request->input('sort_by') ?? 'date_debut',
    //         $request->input('sort_dir') ?? 'desc'
    //     )->paginate($request->input('per_page') ?? 10);

    //     return response()->json($results);
    // }

    // public function index(Request $request)
    // {
    //     $query = Designation::with(['sousDepartement.departement', 'createur']);

    //     // 1. Recherche textuelle (MySQL LIKE)
    //     // UTILISEZ $request->input() ou $request->search
    //     $query->when($request->input('search'), function ($q, $search) {
    //         $q->where('semaine_nom', 'LIKE', "%{$search}%")
    //             ->orWhere('notes_generales', 'LIKE', "%{$search}%");
    //     });
    //     // 2. Filtre par Département (via la relation sousDepartement)
    //     $query->when($request->input('departement_id'), function ($q, $deptId) {
    //         $q->whereHas('sousDepartement', function ($sq) use ($deptId) {
    //             $sq->where('departement_id', $deptId);
    //         });
    //     });

    //     // 3. Filtre par Sous-Département
    //     $query->when($request->input('sous_departement_id'), function ($q, $sdId) {
    //         $q->where('sous_departement_id', $sdId);
    //     });

    //     // 4. Filtre par Statut
    //     $query->when($request->input('statut'), function ($q, $statut) {
    //         $q->where('statut', $statut);
    //     });

    //     //dd($query->toSql(), $query->getBindings()); // Debug : voir la requête générée et les paramètres

    //     // 5. Tri et Pagination
    //     $results = $query->orderBy($request->input('sort_by') ?? 'date_debut', $request->input('sort_dir') ?? 'desc')
    //         ->paginate($request->input('per_page') ?? 10);

    //     return response()->json($results);
    // }

    public function show(Designation $designation)
    {
        $designation->load(['sousDepartement.departement', 'createur']);
        return response()->json($designation);
    }

    // public function edit($id)
    // {
    //     // Charger la désignation avec ses relations clés
    //     $designation = Designation::with(['sousDepartement.departement', 'items.membre'])->findOrFail($id);

    //     // Formater les sous-items pour l'état initial all_designations du Front React
    //     $formattedItems = [];
    //     foreach ($designation->items as $item) {
    //         // Debug : vérifier les données de chaque item
    //         // Supposons que votre table d'items contient le jour (ex: 'lun', 'mar'...) ou que vous l'extrayez de la date effective
    //         $jour = $item->laboratoire_config_id; // Ajustez selon votre colonne réelle stockant le jour

    //         $formattedItems[$item->laboratoire_id][$jour][$item->laboratoire_config_id] = $item->membre_id;

    //         //dd($formattedItems); // Debug : vérifier la structure finale envoyée au Front
    //     }

    //     // On injecte ce tableau virtuel dans l'objet désignation avant l'envoi
    //     $designation->formatted_items = $formattedItems;

    //     //dd($designation); // Debug : vérifier la structure finale envoyée au Front

    //     return Inertia::render('Designations/FormDesignation', [
    //         'departements' => Departement::all(['id', 'nom']),
    //         'designation'  => $designation, // <-- Envoyé au composant !
    //     ]);
    // }

    public function edit($id)
    {
        // On s'assure de bien charger la relation 'laboratoireConfig' pour connaître le nom du jour
        $designation = Designation::with([
            'sousDepartement.departement',
            'items.membre',
            //'items.configuration', // 👈 AJOUT IMPORTANT
        ])->findOrFail($id);

        //dd($designation); // Debug : vérifier la structure finale envoyée au Front

        // Configurer Carbon en français pour correspondre aux labels du Front
        Carbon::setLocale('fr');

        $formattedItems = [];
        foreach ($designation->items as $item) {

            // 1. Extraire le nom du jour en français à partir de la date_effective
            // ex: "2026-07-03" devient "vendredi"
            $jourKey = Carbon::parse($item->date_effective)->translatedFormat('l');

            // Optionnel : Si vos labels au Front commencent par une majuscule (ex: "Vendredi")
            $jourKey = ucfirst($jourKey);

            // 2. Structurer le tableau exactement comme l'attend le composant React :
            // [lab_id][nom_du_jour][requis_id] = membre_id
            $formattedItems[$item->laboratoire_id][$jourKey][$item->laboratoire_config_id] = $item->membre_id;
        }

        //$designation->formatted_items = $formattedItems;


        // Dans votre public function edit($id) juste avant le return :
        $designation->formatted_items = empty($formattedItems) ? (object)[] : $formattedItems;

        //dd($designation->formatted_items); // Debug : vérifier la structure finale envoyée au Front

        return Inertia::render('Designations/FormDesignation', [
            'departements' => Departement::all(['id', 'nom']),
            'designation'  => $designation,
        ]);
    }
    public function store(Request $request)
    {
        // 1. Récupérer la chaîne ISO brute (ex: "2026-07-02T23:00:00.000Z")
        $valeurOrigine     = $request->input('date_debut');
        $dateDebutFormatee = null;

        // On injecte 'en_attente' par défaut si le front ne l'envoie pas
        if (! $request->has('statut')) {
            $request->merge(['statut' => 'en_attente']);
        }

        $statutInitial = 'en_attente';

        // 2. CORRECTION DU FUSEAU HORAIRE AVANT LA VALIDATION
        if (! is_null($valeurOrigine) && $valeurOrigine !== '' && $valeurOrigine !== 'null') {
            try {
                $timezone = $request->input('browser_timezone', config('app.timezone', 'UTC'));

                // On parse en UTC puis on bascule sur la timezone de l'utilisateur
                $baseDate = Carbon::parse($valeurOrigine, 'UTC')->setTimezone($timezone);

                // Le correcteur automatique d'heures (si l'heure reçue est 22h ou 23h UTC)
                if ($baseDate->hour == 23 || $baseDate->hour == 22) {
                    $baseDate->addHours(3);
                }

                // On obtient enfin la vraie date locale désirée (Y-m-d -> "2026-07-03")
                $dateDebutFormatee = $baseDate->format('Y-m-d');

                // 🔥 ON MET À JOUR LA REQUÊTE POUR QUE LA VALIDATION TESTE LA BONNE DATE (03/07)
                $request->merge(['date_debut' => $dateDebutFormatee]);
            } catch (\Exception $e) {
                return back()->withErrors(['date_debut' => 'Le format de la date est invalide.']);
            }
        }

        // 3. PRÉPARER LES RÈGLES DE VALIDATION (Sur la date désormais corrigée à "2026-07-03")
        $dateRules = ['required', 'date'];

        if (! is_null($dateDebutFormatee)) {
            $dateRules[] = Rule::unique('designations', 'date_debut')->where(function ($query) use ($request) {
                return $query->where('sous_departement_id', $request->input('sous_departement_id'));
            });
        }

        // 4. LANCER LA VALIDATION (Elle recevra "2026-07-03", le 'required' fonctionnera si c'était vide)
        $validated = $request->validate([
            'date_debut'          => $dateRules,
            'semaine_nom'         => 'required|string|max:255',
            'sous_departement_id' => 'required|exists:sous_departements,id',
            'notes_generales'     => 'nullable|string',
            'all_designations'    => 'required|array|min:1',
        ], [
            'date_debut.required'       => 'La date de début est obligatoire.',
            'date_debut.date'           => 'Le format de la date est invalide.',
            'date_debut.unique'         => 'Une planification existe déjà pour ce sous-département à cette date.',
            'all_designations.required' => 'Le tableau des désignations est obligatoire.',
            'all_designations.min'      => 'Vous devez affecter au moins une désignation.',
        ]);

        // 5. DATE DE FIN CALCULÉE SUR LA BASE DE LA DATE CORRIGÉE
        $dateFinFormatee = $baseDate->copy()->addDays(6)->format('Y-m-d');

        // 3. LANCER LA VALIDATION AVANT TOUT CALCUL OU INTERACTION BDD
        $validated = $request->validate([
            'date_debut'          => $dateRules,
            'semaine_nom'         => 'required|string|max:255',
            'sous_departement_id' => 'required|exists:sous_departements,id',
            'notes_generales'     => 'nullable|string',
            'all_designations'    => 'required|array|min:1',
        ], [
            'date_debut.required'       => 'La date de début est obligatoire.',
            'date_debut.date'           => 'Le format de la date est invalide.',
            'date_debut.unique'         => 'Une planification existe déjà pour ce sous-département à cette date.',
            'all_designations.required' => 'Le tableau des désignations est obligatoire.',
            'all_designations.min'      => 'Vous devez affecter au moins une désignation.',
        ]);

        // 5. VÉRIFICATION SI AU MOINS UN MEMBRE A ÉTÉ CHOISI DANS LA GRILLE
        $hasAtLeastOneMember = false;
        if (is_array($request->input('all_designations'))) {
            foreach ($request->input('all_designations') as $labId => $jours) {
                foreach ($jours as $jourNom => $requis) {
                    foreach ($requis as $requisId => $membreId) {
                        if (! empty($membreId)) {
                            $hasAtLeastOneMember = true;
                            break 3;
                        }
                    }
                }
            }
        }

        if (! $hasAtLeastOneMember) {
            return back()->withErrors([
                'all_designations' => 'Vous devez sélectionner au moins un membre dans la grille des désignations.',
            ])->withInput();
        }

        // 6. CRÉER LA DÉSIGNATION PRINCIPALE
        $designation = Designation::create([
            'semaine_nom'         => $validated['semaine_nom'],
            'sous_departement_id' => $validated['sous_departement_id'],
            'date_debut'          => $dateDebutFormatee,
            'date_fin'            => $dateFinFormatee,
            'statut'              => $statutInitial,
            'createur_id'         => $request->user()->id,
        ]);

        // 7. SÉCURISATION DES CLÉS DE JOURS POUR LA BOUCLE (Tout en minuscules)
        $joursAjouter = [
            'vendredi' => 0,
            'samedi'   => 1,
            'dimanche' => 2,
            'lundi'    => 3,
            'mardi'    => 4,
            'mercredi' => 5,
            'jeudi'    => 6,
        ];

        // 8. INSERTION DES ENREGISTREMENTS LIÉS
        foreach ($request->input('all_designations') as $labId => $jours) {
            foreach ($jours as $jourNom => $requis) {
                foreach ($requis as $requisId => $membreId) {

                    if (! empty($membreId)) {
                        $dateEffective = $baseDate->copy();

                        $configJour = LaboratoireConfig::where('jour_label', $jourNom)
                            ->where('laboratoire_id', $labId)
                            ->first();

                        if ($configJour && $configJour->type_config !== 'fixe') {
                            $joursEnPlus = $joursAjouter[strtolower($jourNom)] ?? 0;
                            $dateEffective->addDays($joursEnPlus);
                        }

                        DesignationItem::create([
                            'designation_id'        => $designation->id,
                            'laboratoire_id'        => $labId,
                            'laboratoire_config_id' => $requisId,
                            'membre_id'             => $membreId,
                            'date_effective'        => $dateEffective->format('Y-m-d'),
                        ]);
                    }
                }
            }
        }

        return redirect()->route('designations.index')
            ->with('success', 'La planification a été créée et est en attente de validation.');
    }
    // public function update(Request $request, Designation $designation)
    // {
    //     $validated = $request->validate([
    //         'semaine_nom'         => 'required|string|max:255',
    //         'sous_departement_id' => 'required|exists:sous_departements,id',
    //         'statut'              => 'required|in:publiee,en_attente,inactive',
    //         // 'notes_generales'     => 'nullable|string',
    //     ]);

    //     $designation->update($validated);

    //     return response()->json($designation);
    // }

    public function update(Request $request, Designation $designation)
    {
        //dd($request->all()); // Debug : vérifier les données reçues du front

        // Si le front-end n'envoie pas de statut, on conserve le statut actuel de la base de données
        if (!$request->has('statut')) {
            $request->merge(['statut' => $designation->statut ?? 'en_attente']);
        }

        // 1. Récupérer la chaîne ISO brute de la date
        $valeurOrigine     = $request->input('date_debut');
        $dateDebutFormatee = null;

        // 2. CORRECTION DU FUSEAU HORAIRE AVANT LA VALIDATION
        if (! is_null($valeurOrigine) && $valeurOrigine !== '' && $valeurOrigine !== 'null') {
            try {
                $timezone = $request->input('browser_timezone', config('app.timezone', 'UTC'));

                // Si c'est déjà un format Y-m-d (parce que bloqué en édition), Carbon le gérera très bien
                $baseDate = Carbon::parse($valeurOrigine, 'UTC')->setTimezone($timezone);

                // Le correcteur automatique d'heures (décalage UTC)
                if ($baseDate->hour == 23 || $baseDate->hour == 22) {
                    $baseDate->addHours(3);
                }

                $dateDebutFormatee = $baseDate->format('Y-m-d');
                $request->merge(['date_debut' => $dateDebutFormatee]);
            } catch (\Exception $e) {
                return back()->withErrors(['date_debut' => 'Le format de la date est invalide.']);
            }
        }

        // 3. PRÉPARER LES RÈGLES DE VALIDATION (Avec exception pour la désignation en cours d'édition)
        $dateRules = ['required', 'date'];

        if (! is_null($dateDebutFormatee)) {
            // 🔥 ->ignore($designation->id) est CRUCIAL ici pour éviter que Laravel ne refuse la mise à jour
            // en croyant que la date (qui appartient déjà à cette fiche) est un doublon.
            $dateRules[] = Rule::unique('designations', 'date_debut')
                ->ignore($designation->id)
                ->where(function ($query) use ($request) {
                    return $query->where('sous_departement_id', $request->input('sous_departement_id'));
                });
        }

        // 4. LANCER LA VALIDATION DE LA FICHE PRINCIPALE
        $validated = $request->validate([
            'date_debut'          => $dateRules,
            'semaine_nom'         => 'required|string|max:255',
            'sous_departement_id' => 'required|exists:sous_departements,id',
            'statut'              => 'required|in:publiee,en_attente,inactive',
            'all_designations'    => 'required|array|min:1',
        ], [
            'date_debut.required'       => 'La date de début est obligatoire.',
            'date_debut.unique'         => 'Une planification existe déjà pour ce sous-département à cette date.',
            'all_designations.required' => 'Le tableau des désignations est obligatoire.',
        ]);

        // 5. VÉRIFICATION DE LA GRILLE DES MEMBRES
        $hasAtLeastOneMember = false;
        if (is_array($request->input('all_designations'))) {
            foreach ($request->input('all_designations') as $labId => $jours) {
                foreach ($jours as $jourNom => $requis) {
                    foreach ($requis as $requisId => $membreId) {
                        if (! empty($membreId)) {
                            $hasAtLeastOneMember = true;
                            break 3;
                        }
                    }
                }
            }
        }

        if (! $hasAtLeastOneMember) {
            return back()->withErrors([
                'all_designations' => 'Vous devez sélectionner au moins un membre dans la grille des désignations.',
            ])->withInput();
        }

        // 6. CALCULER LA DATE DE FIN
        $dateFinFormatee = $baseDate->copy()->addDays(6)->format('Y-m-d');

        // 7. METTRE À JOUR LA DÉSIGNATION PARENTE
        $designation->update([
            'semaine_nom'         => $validated['semaine_nom'],
            'sous_departement_id' => $validated['sous_departement_id'],
            'date_debut'          => $dateDebutFormatee,
            'date_fin'            => $dateFinFormatee,
            'statut'              => $validated['statut'],
        ]);

        // 8. SYNCHRONISATION DES ITEMS (Le secret de la mise à jour)
        // Au lieu de faire des "update" complexes, on supprime proprement les anciens items
        // liés à cette désignation, puis on réinsère la nouvelle grille modifiée.
        $designation->items()->delete();

        $joursAjouter = [
            'vendredi' => 0,
            'samedi'   => 1,
            'dimanche' => 2,
            'lundi'    => 3,
            'mardi'    => 4,
            'mercredi' => 5,
            'jeudi'    => 6,
        ];

        // 9. RE-CRÉATION DES ITEMS MIS À JOUR
        foreach ($request->input('all_designations') as $labId => $jours) {
            foreach ($jours as $jourNom => $requis) {
                foreach ($requis as $requisId => $membreId) {

                    if (! empty($membreId)) {
                        // On repart de la date de début validée (Y-m-d)
                        $dateEffective = Carbon::parse($dateDebutFormatee);

                        // Sécurité : On passe le nom du jour en minuscules pour correspondre au dictionnaire
                        $jourNomMinuscule = strtolower($jourNom);

                        // Calcul de la date dynamique selon le jour de la grille
                        if (array_key_exists($jourNomMinuscule, $joursAjouter)) {
                            $joursEnPlus = $joursAjouter[$jourNomMinuscule];
                            $dateEffective->addDays($joursEnPlus);
                        }

                        // Insertion de la ligne
                        DesignationItem::create([
                            'designation_id'        => $designation->id,
                            'laboratoire_id'        => $labId,
                            'laboratoire_config_id' => $requisId, // 18961, 18966...
                            'membre_id'             => $membreId,     // 1, 2...
                            'date_effective'        => $dateEffective->format('Y-m-d'),
                        ]);
                    }
                }
            }
        }

        //dd($designation->formatted_items); // Debug : vérifier la structure finale après mise à jour

        // 10. REDIRECTION INERTIA (ou réponse JSON selon votre configuration globale,
        // mais comme vous utilisez useForm de Inertia, redirect->route est l'idéal pour rafraîchir le composant)
        return redirect()->route('designations.index')
            ->with('success', 'La planification a été mise à jour avec succès.');
    }
    public function destroy(Designation $designation)
    {
        $designation->delete();
        return response()->json(null, 204);
    }
    public function duplicate(Designation $designation)
    {
        $newDesignation               = $designation->replicate();
        $newDesignation->semaine_nom .= ' (Copie)';
        $newDesignation->createur_id  = auth()->id();
        $newDesignation->save();

        return response()->json($newDesignation, 201);
    }

    /**
     * Affiche le formulaire de création.
     * On n'envoie QUE les départements pour alléger le payload initial.
     */
    public function create()
    {
        // On récupère uniquement l'ID et le nom pour la performance
        // auth()->user() est implicitement géré par le middleware 'web'
        $departements = Departement::select('id', 'nom')
            ->orderBy('nom')
            ->get();

        return Inertia::render('Designations/CreateDesignation', [
            'departements' => $departements,
            // On peut envoyer d'autres constantes si nécessaire (ex: types de config)
            'config_types' => ['fixe', 'variable'],
        ]);
    }

    // LabController.php (Version MySQL)
    public function searchMembers(Request $request, Laboratoire $lab)
    {
        $query = $request->input('q');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // MySQL gère le LIKE sans distinction de casse par défaut avec les collations courantes
        // return $lab->membres()
        //     ->where('name', 'LIKE', "%{$query}%")
        //     ->select('id', 'name')
        //     ->limit(10)
        //     ->get();

        return Membre::query()
            ->whereRaw('LOWER(name) LIKE ?', ["%{$query}%"])
            ->select('id', 'name')
            ->limit(10)
            ->get();
    }

    // /** */
    //  * 1.Charger leslabospourunsous - département spécifique
    //  *  /
    public function getLabsBySousDept(SousDepartement $sous_departement)
    {
        // On récupère les labos liés à ce sous-département
        $labs = $sous_departement->laboratoires()->select('id', 'nom')->get();

        return response()->json($labs);
    }

    /**
     * 2. Charger la configuration complète (jours + requis)
     */
    public function getLabConfig(Laboratoire $lab)
    {
        // On charge le labo avec ses relations (ex: jours d'ouverture, équipements requis)
        // 'config' peut être une relation ou un champ JSON selon votre structure
        // On charge les jours d'ouverture ET, pour chaque jour, ses postes requis
        $lab->load(['config_jours.requis']);

        return response()->json($lab);

        // return response()->json([
        //     'id'           => $lab->id,
        //     'nom'          => $lab->nom,
        //     'jours'        => $lab->jours_ouverture, // ex: ['Lundi', 'Mardi', ...]
        //     'requis'       => $lab->besoins_specifiques,
        //     'capacite_max' => $lab->capacite,
        // ]);
    }

    public function getLabMembers(Request $request, Laboratoire $lab)
    {
        // On récupère ce que l'utilisateur a tapé
        $search = $request->query('query');
        //dd($search);
        $query = $lab->membres()
            ->where('membres.est_actif', true);

        // Si l'utilisateur a tapé quelque chose, on filtre sur le nom
        if (! empty($search)) {
            $query->where('membres.nom', 'like', "%{$search}%");
        }

        // On limite à 15 résultats maximum pour la performance
        $membres = $query->select('membres.id', 'membres.nom')
            ->limit(15)
            ->get();

        //dd($membres);
        return response()->json($membres->values()->all());
    }

    /**
     * Génère et affiche le rapport PDF d'une désignation hebdomadaire.
     */
    public function telechargerRapport($id)
    {
        // 1. Récupération de la désignation avec toutes ses données réelles
        $designation = Designation::with([
            'sousDepartement.departement',
            'items.membre',
            'items.laboratoire'
        ])->findOrFail($id);

        // 2. Préparation des variables textuelles réelles
        $info1 = $designation->semaine_nom; // Ex: "Semaine 27 - 2026"
        $info2 = "Département : " . ($designation->sousDepartement->departement->nom ?? 'N/A') .
            " (" . ($designation->sousDepartement->nom ?? 'N/A') . ")";

        // 3. Récupération et formatage des items réels de la BDD
        // Vous pouvez trier par date effective pour que le tableau du PDF soit chronologique
        $items = $designation->items->sortBy('date_effective')->map(function ($item) {
            return [
                'date' => Carbon::parse($item->date_effective)->translatedFormat('d/m/Y'),
                'jour' => ucfirst(Carbon::parse($item->date_effective)->translatedFormat('l')),
                'labo' => $item->laboratoire->nom ?? 'N/A',
                'membre' => $item->membre->nom ?? 'N/A',
                'prenom' => $item->membre->prenom ?? 'N/A',
                'fonction' => $item->membre->fonction ?? 'N/A',
                // Vous pouvez ajouter d'autres attributs si votre vue blade en a besoin :
                'observations' => $item->observations,
            ];
        });

        // 4. Chargement de la vue 'resources/views/pdf/designation.blade.php' avec les vraies données
        $pdf = Pdf::loadView('pdf.designation', compact('designation', 'info1', 'info2', 'items'));

        // 5. Configuration du format de la page
        $pdf->setPaper('a4', 'portrait');

        // 6. Affichage direct dans le navigateur (recommandé avec target="_blank" côté React)
        $nomFichier = 'rapport-planification-' . $designation->id . '-' . Carbon::parse($designation->date_debut)->format('Y-m-d') . '.pdf';

        return $pdf->stream($nomFichier);
    }
}
