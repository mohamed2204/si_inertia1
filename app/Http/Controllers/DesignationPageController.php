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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DesignationPageController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();

        // 1. Détection adaptative des rôles d'Administration et Direction
        $isSuperAdmin    = $user->is_admin || $user->groups()->where('code', 'admin')->exists();
        $isDirection     = $user->groups()->where('name', 'Direction / Administration')->exists();
        $hasAbsoluteView = $isSuperAdmin || $isDirection;

        // 2. Récupération des IDs de tous les groupes de l'utilisateur connecté
        $userGroupIds = $user->groups()->pluck('groups.id')->toArray();

        // 3. Vérification des permissions GLOBALES au niveau du module
        $hasGlobalRead = $hasAbsoluteView || DB::table('permissions_groupes')
            ->whereIn('group_id', $userGroupIds)
            ->where(['module_type' => 'designations', 'type_action' => 'lecture'])
            ->exists();

        // Sécurité de base impérative : si pas de lecture globale accordée, accès interdit
        if (! $hasGlobalRead) {
            abort(403, "Vous n'avez pas accès au module des désignations.");
        }

        $hasGlobalWrite = $hasAbsoluteView || DB::table('permissions_groupes')
            ->whereIn('group_id', $userGroupIds)
            ->where(['module_type' => 'designations', 'type_action' => 'modification'])
            ->exists();

        $hasGlobalDelete = $hasAbsoluteView || DB::table('permissions_groupes')
            ->whereIn('group_id', $userGroupIds)
            ->where(['module_type' => 'designations', 'type_action' => 'suppression'])
            ->exists();

        // Préparer la requête SQL de base Eloquent
        $query = Designation::with(['sousDepartement.departement', 'createur']);

        // =========================================================================
        // SÉCURITÉ ET DROITS PIVOTS LOCAUX (PÉRIMÈTRE DES LABOS)
        // =========================================================================
        $userLabAccess = [];

        if (! $hasAbsoluteView) {
            // Construction du dictionnaire des accès de l'utilisateur [sous_departement_id => niveau_acces]
            $userLabAccess = DB::table('group_sous_departement')
                ->whereIn('group_id', $userGroupIds)
                ->get()
                ->groupBy('sous_departement_id')
                ->map(function ($items) {
                    // En cas de multi-groupes aux droits conflictuels, on conserve le privilège le plus élevé
                    $priorite = ['total' => 3, 'ecriture' => 2, 'lecture' => 1, 'aucune' => 0];
                    return $items->sortByDesc(fn($item) => $priorite[$item->niveau_acces] ?? 0)->first()->niveau_acces;
                })
                ->toArray();

            // Filtrage strict au niveau SQL : l'utilisateur ne voit que ses laboratoires assignés
            $allowedSousDeptIds = array_keys($userLabAccess);
            $query->whereIn('sous_departement_id', $allowedSousDeptIds);
        }

        // =========================================================================
        // APPLICATION DES FILTRES DYNAMIQUES DE RECHERCHE
        // =========================================================================
        $query->when($request->input('search'), function ($q, $search) {
            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('semaine_nom', 'LIKE', "%{$search}%")
                    ->orWhere('notes_generales', 'LIKE', "%{$search}%");
            });
        });

        $query->when($request->input('departement_id'), function ($q, $deptId) {
            $q->whereHas('sousDepartement', function ($sq) use ($deptId) {
                $sq->where('departement_id', $deptId);
            });
        });

        $query->when($request->input('sous_departement_id'), function ($q, $sdId) {
            $q->where('sous_departement_id', $sdId);
        });

        $query->when($request->input('statut'), function ($q, $statut) {
            $q->where('statut', $statut);
        });

        // =========================================================================
        // EXÉCUTION DE LA PAGINATION ET DES TRIS
        // =========================================================================
        $paginatedResults = $query->orderBy(
            $request->input('sort_by') ?? 'date_debut',
            $request->input('sort_dir') ?? 'desc'
        )->paginate($request->input('per_page') ?? 10);

        // =========================================================================
        // TRANSFORMATION LIGNE PAR LIGNE (Injection des permissions d'action)
        // =========================================================================
        $paginatedResults->through(function ($designation) use ($hasAbsoluteView, $userLabAccess, $hasGlobalWrite, $hasGlobalDelete) {
            // 1. Droits pour Admin ou Direction
            if ($hasAbsoluteView) {
                $canEdit   = $hasGlobalWrite;
                $canDelete = $hasGlobalDelete;
            }
            // 2. Droits pour Utilisateur standard (déduits du dictionnaire des labos)
            else {
                $labLevel = $userLabAccess[$designation->sous_departement_id] ?? 'aucune';

                // Modifier : Droit d'écriture global requis + niveau local 'ecriture' ou 'total'
                $canEdit = $hasGlobalWrite && in_array($labLevel, ['ecriture', 'total']);

                // Supprimer : Droit de suppression global requis + niveau local 'total' uniquement
                $canDelete = $hasGlobalDelete && ($labLevel === 'total');
            }

            // Formatage de la chaîne d'emplacement combinée
            $emplacement = $designation->sousDepartement && $designation->sousDepartement->departement
                ? "{$designation->sousDepartement->departement->nom} - {$designation->sousDepartement->nom}"
                : ($designation->sousDepartement->nom ?? 'Non assigné');

            // Attachement des attributs virtuels consommés par React
            $designation->can_edit            = $canEdit;
            $designation->can_delete          = $canDelete;
            $designation->emplacement_formate = $emplacement;

            return $designation;
        });

        // Calcul du droit de création global (Vérifie si l'utilisateur possède au moins un labo en écriture/total)
        $canCreateGlobally = $hasGlobalWrite && (
            $hasAbsoluteView ||
            DB::table('group_sous_departement')
                ->whereIn('group_id', $userGroupIds)
                ->whereIn('niveau_acces', ['ecriture', 'total'])
                ->exists()
        );

        // =========================================================================
        // AIGUILLAGE INTELLIGENT DE LA RÉPONSE
        // =========================================================================
        if ($request->wantsJson()) {
            // Cas 1 : Requête Axios (Filtrage, Tri, Changement de page) -> Renvoi strict du flux JSON paginé
            return response()->json($paginatedResults);
        }

        // Cas 2 : Premier chargement de la page -> Inertia rend le composant structurel
        return Inertia::render('Designations/IndexApi', [
            'results'            => null, // Le tableau démarre vide, Axios l'alimente immédiatement après le montage
            'initialDepartments' => Departement::orderBy('nom')->get(),
            'filters'            => $request->only(['search', 'departement_id', 'sous_departement_id', 'statut']),
            'can_create'         => $canCreateGlobally,
        ]);
    }
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

    public function edit($id)
    {
        // Charger la désignation avec ses relations clés
        $designation = Designation::with(['sousDepartement.departement', 'items.membre'])->findOrFail($id);

        // Formater les sous-items pour l'état initial all_designations du Front React
        $formattedItems = [];
        foreach ($designation->items as $item) {
                                                  // Debug : vérifier les données de chaque item
                                                  // Supposons que votre table d'items contient le jour (ex: 'lun', 'mar'...) ou que vous l'extrayez de la date effective
            $jour = $item->laboratoire_config_id; // Ajustez selon votre colonne réelle stockant le jour

            $formattedItems[$item->laboratoire_id][$jour][$item->laboratoire_config_id] = $item->membre_id;

            //dd($formattedItems); // Debug : vérifier la structure finale envoyée au Front
        }

        // On injecte ce tableau virtuel dans l'objet désignation avant l'envoi
        $designation->formatted_items = $formattedItems;

        return Inertia::render('Designations/FormDesignation', [
            'departements' => Departement::all(['id', 'nom']),
            'designation'  => $designation, // <-- Envoyé au composant !
        ]);
    }
    public function store(Request $request)
    {
        // On injecte 'en_attente' par défaut si le front ne l'envoie pas
        if (! $request->has('statut')) {
            $request->merge(['statut' => 'en_attente']);
        }

        // Au moment de créer votre Désignation principale :
        // 3. Forcer le statut initial à 'en_attente'
        $statutInitial = 'en_attente';

        // 1. Initialiser la date de départ (Vendredi)

        // 1. On récupère la timezone du navigateur (par défaut UTC si absente)
        $timezone = $request->input('browser_timezone', config('app.timezone', 'UTC'));

        try {
            // 2. On parse DIRECTEMENT la chaîne reçue du formulaire.
            // On indique à Carbon que la chaîne reçue est en UTC, puis on la convertit
            // immédiatement vers la timezone de l'utilisateur.
            $baseDate = Carbon::parse($request->input('date_debut'), 'UTC')->setTimezone($timezone);

        } catch (\Exception $e) {
            return back()->withErrors(['date_debut' => 'Le format de la date est invalide.']);
        }

        // 2. LE CORRECTEUR AUTOMATIQUE :
        // Si l'heure est fixée à 23h00, c'est le décalage UTC classique.
        // En ajoutant 2 heures, on force la date à passer à 01h00 du MATIN LE LENDEMAIN (le bon jour !).
        // Si la date était déjà à 00:00:00, ajouter 2 heures reste sur la même journée.
        if ($baseDate->hour == 23 || $baseDate->hour == 22) {
            $baseDate->addHours(3);
        }

        // 3. On extrait les chaînes propres au format standard MySQL (Y-m-d)
        $dateDebutFormatee = $baseDate->format('Y-m-d');

        // Si vous faites un dd() ici, vous verrez enfin la date exacte sélectionnée sur l'écran (le 15 mai) !
        //dd($dateDebutFormatee);

        // 2. Calculer automatiquement la date de fin (+ 6 jours pour faire une semaine complète)
        // On utilise ->copy() pour ne pas altérer la variable $baseDate originale
        $dateFinFormatee = $baseDate->copy()->addDays(6)->format('Y-m-d');

        // 2. On injecte la date nettoyée dans la requête AVANT la validation
        // pour que Laravel valide "2026-05-15" et non la chaîne ISO originale.
        $request->merge(['date_debut' => $dateDebutFormatee]);

        // 3. Validation stricte des doublons
        $validated = $request->validate([
            'semaine_nom'         => 'required|string|max:255',
            'sous_departement_id' => 'required|exists:sous_departements,id',
            'notes_generales'     => 'nullable|string',

            // LA RÈGLE COMPOSITE UNIQUE :
            'date_debut'          => [
                'required',
                'date',
                Rule::unique('designations', 'date_debut')->where(function ($query) use ($request) {
                    return $query->where('sous_departement_id', $request->input('sous_departement_id'));
                }),
            ],
        ], [
            // Message d'erreur personnalisé en français pour le SweetAlert2 du Front !
            'date_debut.unique' => 'Une planification existe déjà pour ce sous-département à cette date.',
        ]);

        // 2. Créer la désignation principale
        $designation = Designation::create([
            'semaine_nom'         => $validated['semaine_nom'],
            'sous_departement_id' => $validated['sous_departement_id'],
            'date_debut'          => $dateDebutFormatee,
            'date_fin'            => $dateFinFormatee,
            'statut'              => $statutInitial,
            // 'notes_generales'     => $validated['notes_generales'],
            'createur_id'         => $request->user()->id,
        ]);

        // 3. Tableau de correspondance pour l'ajout des jours (Mode Calendrier)
        // Ajustez les clés ('Vendredi', 'Samedi'...) selon les valeurs exactes stockées dans votre colonne 'jour'
        $joursAjouter = [
            'Vendredi' => 0,
            'Samedi'   => 1,
            'Dimanche' => 2,
            'Lundi'    => 3,
            'Mardi'    => 4,
            'Mercredi' => 5,
            'jeudi'    => 6,
        ];

        // 4. Parcourir la grille all_designations
        if ($request->has('all_designations')) {
            foreach ($request->input('all_designations') as $labId => $jours) {
                foreach ($jours as $jourNom => $requis) {
                    foreach ($requis as $requisId => $membreId) {

                        if (! empty($membreId)) {

                            // Par défaut, la date effective est la date de début
                            $dateEffective = $baseDate->copy();

                            // Récupérer la configuration du jour pour vérifier son type (fixe ou calendrier)
                            // 'requisId' correspond à l'ID de la ligne requise qui est liée à la config du jour
                            $configJour = LaboratoireConfig::where('jour_label', $jourNom)
                                ->where('laboratoire_id', $labId)
                                ->first();

                            if ($configJour) {
                                // SI le type est 'calendrier' (ou n'est PAS 'fixe')
                                if ($configJour->type_config !== 'fixe') {
                                    // On récupère le nombre de jours à ajouter (ex: samedi = 1)
                                    // On passe le nom du jour en minuscule pour éviter les surprises
                                    $joursEnPlus = $joursAjouter[strtolower($jourNom)] ?? 0;
                                    $dateEffective->addDays($joursEnPlus);
                                }
                                // SI le type est 'fixe', on ne fait rien, la date reste $baseDate (+0)
                            }

                            // Insertion finale avec la bonne date calculée
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
        }

        // 6. Redirection automatique vers l'index avec un message flash de succès
        return redirect()->route('designations.index')
            ->with('success', 'La planification a été créée et est en attente de validation.');

        // ... reste de votre logique (redirection ou réponse JSON)

        //return response()->json($designation, 201);
    }

    public function update(Request $request, Designation $designation)
    {
        $validated = $request->validate([
            'semaine_nom'         => 'required|string|max:255',
            'sous_departement_id' => 'required|exists:sous_departements,id',
            'statut'              => 'required|in:publiee,en_attente,inactive',
            // 'notes_generales'     => 'nullable|string',
        ]);

        $designation->update($validated);

        return response()->json($designation);
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

}
