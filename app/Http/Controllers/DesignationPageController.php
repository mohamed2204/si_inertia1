<?php
namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\Designation;
use App\Models\Laboratoire;
use App\Models\Membre;
use App\Models\SousDepartement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DesignationPageController extends Controller
{
    public function index(Request $request)
    {
        $query = Designation::with(['sousDepartement.departement', 'createur']);

        // 1. Recherche textuelle (MySQL LIKE)
        // UTILISEZ $request->input() ou $request->search
        $query->when($request->input('search'), function ($q, $search) {
            $q->where('semaine_nom', 'LIKE', "%{$search}%")
                ->orWhere('notes_generales', 'LIKE', "%{$search}%");
        });
        // 2. Filtre par Département (via la relation sousDepartement)
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

        //dd($query->toSql(), $query->getBindings()); // Debug : voir la requête générée et les paramètres

        // 5. Tri et Pagination
        $results = $query->orderBy($request->input('sort_by') ?? 'created_at', $request->input('sort_dir') ?? 'desc')
            ->paginate($request->input('per_page') ?? 10);

        return response()->json($results);
    }

    public function show(Designation $designation)
    {
        $designation->load(['sousDepartement.departement', 'createur']);
        return response()->json($designation);
    }

    public function edit(Designation $designation)
    {
        $designation->load(['sousDepartement.departement', 'createur']);

        return response()->json([
            'designation'  => $designation,
            'departements' => Departement::all(),
            'config_types' => ['fixe', 'variable'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'semaine_nom'         => 'required|string|max:255',
            'sous_departement_id' => 'required|exists:sous_departements,id',
            'statut'              => 'required|in:active,inactive',
            'notes_generales'     => 'nullable|string',
        ]);

        $designation = Designation::create([
             ...$validated,
            'createur_id' => auth()->id(),
        ]);

        return response()->json($designation, 201);
    }

    public function update(Request $request, Designation $designation)
    {
        $validated = $request->validate([
            'semaine_nom'         => 'required|string|max:255',
            'sous_departement_id' => 'required|exists:sous_departements,id',
            'statut'              => 'required|in:active,inactive',
            'notes_generales'     => 'nullable|string',
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
