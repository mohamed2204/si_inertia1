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
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DesignationPageController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = Designation::with(['sousDepartement.departement', 'createur']);

        // ==========================================
        // SÉCURITÉ : Filtrage strict par Groupe / Sous-Département
        // ==========================================
        // Si ce n'est PAS un administrateur global (Direction)
        if (! $user->groups()->where('name', 'Direction / Administration')->exists()) {

            // On récupère les sous-départements autorisés pour cet utilisateur
            $allowedSousDeptIds = $user->groups()
                ->join('group_sous_departement', 'group_id', '=', 'group_sous_departement.groupe_id')
                ->pluck('group_sous_departement.sous_departement_id')
                ->toArray();

            // On bloque immédiatement la requête sur ses périmètres autorisés
            $query->whereIn('sous_departement_id', $allowedSousDeptIds);
        }

        dd($query->toSql(), $query->getBindings()); // Debug : voir la requête générée et les paramètres

        // ==========================================
        // FILTRES EXISTANTS (Modifiés pour la sécurité)
        // ==========================================

        // 1. Recherche textuelle (Encapsulée dans une fonction pour ne pas casser le whereIn de sécurité)
        $query->when($request->input('search'), function ($q, $search) {
            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('semaine_nom', 'LIKE', "%{$search}%")
                    ->orWhere('notes_generales', 'LIKE', "%{$search}%");
            });
        });

        // 2. Filtre par Département
        $query->when($request->input('departement_id'), function ($q, $deptId) {
            $q->whereHas('sousDepartement', function ($sq) use ($deptId) {
                $sq->where('departement_id', $deptId);
            });
        });

        // 3. Filtre par Sous-Département
        $query->when($request->input('sous_departement_id'), function ($q, $sdId) {
            $q->where('sous_departement_id', $sdId);
        });

        // 4. Filtre par Statut
        $query->when($request->input('statut'), function ($q, $statut) {
            $q->where('statut', $statut);
        });

        // 5. Tri et Pagination
        $results = $query->orderBy(
            $request->input('sort_by') ?? 'date_debut',
            $request->input('sort_dir') ?? 'desc'
        )->paginate($request->input('per_page') ?? 10);

        return response()->json($results);
    }
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
