<?php
namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\Designation;
use App\Models\Laboratoire;
use App\Models\LaboratoireConfig;
use App\Models\Membre;
use App\Models\SousDepartement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DesignationController extends Controller
{
    // Appelé par la route GET
    // public function index()
    // {
    //     $data = Inertia::render('Designations/Index', [
    //         // On charge les désignations avec leurs relations pour la DataTable
    //         'designations'     => Designation::with([
    //             'sousDepartement.departement',
    //             'items.membre',
    //             'items.laboratoire', // Indispensable pour les badges
    //         ])->latest()->get(),

    //         // Données pour les formulaires (Dropdowns et MultiSelect)
    //         'departements'     => Departement::all(),

    //         'sousDepartements' => SousDepartement::all(),

    //         'laboratoires'     => Laboratoire::with(['labRequis' => function ($query) {
    //             $query->orderBy('ordre', 'asc'); // Force l'ordre au cas où
    //         }, 'labRequis.roleTache'])->get(),

    //         //'membres'          => User::select('id', DB::raw("name as nom_complet"))->get(), // Ajustez selon votre table
    //         'membres'          => Membre::all(), // Liste pour le MultiSelect
    //     ]);
    //     // dd($data);
    //     return $data;
    // }
    // public function index()
    // {
    //     return Inertia::render('Designations/Index', [
    //         // Chargement de la nouvelle hiérarchie : Lab -> Jours -> Postes (Requis)
    //         'departements' => Departement::with([
    //             'sousDepartements.laboratoires.config_jours.requis',
    //         ])->get(),

    //         'membres'      => Membre::select('id', 'nom')
    //             ->orderBy('nom')
    //             ->get(),

    //         'designations' => Designation::with([
    //             'sousDepartement.departement',
    //             // On adapte aussi le chargement de l'historique si nécessaire
    //             'items.laboratoire',
    //             'items.membre',
    //         ])
    //             ->latest()
    //             ->paginate(10)
    //             ->withQueryString(),
    //     ]);
    // }

    public function index()
    {
        return Inertia::render('Designations/Index', [
            'departements' => Departement::select('id', 'nom')->get(),
            // 'sousDepartements' => SousDepartement::select('id', 'nom', 'departement_id')->get(),
            // 'laboratoires'     => Laboratoire::with(['config_jours.requis'])
            //     ->select('id', 'nom', 'sous_departement_id')
            //     ->get(),
            // 'membres'          => Membre::select('id', 'nom')
            //     ->orderBy('nom')
            //     ->get(),
            // 'designations'     => Designation::with([
            //     'sousDepartement.departement',
            //     'items.laboratoire',
            //     'items.membre',
            // ])
            //     ->latest()
            //     ->paginate(10)
            //     ->withQueryString(),
        ]);
    }

    public function create()
    {
        dd("Formulaire de création de désignation");
        return Inertia::render('Designations/Create', [
            'departements'     => Departement::all(),
            'sousDepartements' => SousDepartement::all(),
            //'laboratoires'     => Laboratoire::with(['config_jours.requis'])->get(),
            'laboratoires'     => Laboratoire::with(['config_jours.requis'])->get(),
            'membres'          => Membre::orderBy('nom')->get(),
        ]);
    }
    public function store(Request $request)
    {
        //dd($request->all());
        $allDesignations = $request->input('all_designations', []);
        $semaineNom      = $request->input('semaine_nom');

        // On valide que les champs sont présents et non nulls ('required')
        // $validated = $request->validate([
        //     'date_debut'          => 'required|date',
        //     'sous_departement_id' => 'required|integer|exists:sous_departements,id',
        // ]);

        $dateDebut  = $request->input('date_debut');
        $sousDeptId = $request->input('sous_departement_id');

        if (is_null($dateDebut) || is_null($sousDeptId)) {
            // Option A : Retourner une erreur flash
            return back()->withErrors(['msg' => 'La date et le sous-département sont obligatoires.']);

            // Option B : Lever une exception
            // abort(400, "Données manquantes");
        }

        DB::beginTransaction();
        try {
            // 1. On assure l'existence de l'en-tête (la semaine)
            $designation = Designation::updateOrCreate(
                [
                    'semaine_nom'         => $semaineNom,
                    'sous_departement_id' => $sousDeptId,
                ],
                [
                    'date_debut'  => $dateDebut,
                    'date_fin'    => Carbon::parse($dateDebut)->addDays(7),
                    'createur_id' => auth()->id(),
                    'statut'      => 'publié',
                ]
            );

            // 2. On boucle sur les données reçues
            foreach ($allDesignations as $labId => $jours) {
                Log::info("Traitement du laboratoire ID: $labId");

                foreach ($jours as $jourSlug => $requisGroup) {
                    Log::info("Traitement du jour: $jourSlug pour le laboratoire ID: $labId");

                    // On récupère la config du jour (ven, sam...) pour ce lab
                    $config = LaboratoireConfig::where('laboratoire_id', $labId)
                        ->where('jour', $jourSlug)
                        ->first();

                    if (! $config) {
                        continue;
                    }

                    foreach ($requisGroup as $requisId => $membreId) {
                        Log::info("Traitement du poste requis ID: $requisId pour le jour: $jourSlug et laboratoire ID: $labId");
                        // Si la case est vidée dans l'interface
                        if (! $membreId) {
                            $designation->items()
                                ->where('laboratoire_id', $labId)
                                ->where('laboratoire_config_id', $config->id)
                                ->delete();
                            continue;
                        }

                        // 3. UpdateOrCreate sur l'item
                        // On regarde directement la colonne de type dans la table config
                        if ($config->type_config === 'calendrier') {
                            // Si c'est du calendrier, on calcule : date_debut + (ordre - 1)
                            $dateEffective = Carbon::parse($dateDebut)->addDays($config->ordre_affichage - 1);
                        } else {
                            // Sinon (type 'fixe'), on garde la date_debut brute
                            $dateEffective = $dateDebut;
                        }

                        $designation->items()->updateOrCreate(
                            [
                                'laboratoire_id'        => $labId,
                                'laboratoire_config_id' => $config->id,
                            ],
                            [
                                'membre_id'      => $membreId,
                                'date_effective' => $dateEffective,
                            ]
                        );
                        // $designation->items()->updateOrCreate(
                        //     [
                        //         'laboratoire_id' => $labId,
                        //         'laboratoire_config_id' => $config->id,
                        //         // Si vous avez plusieurs postes par jour, ajoutez la colonne requis_id ici
                        //     ],
                        //     [
                        //         'membre_id' => $membreId,
                        //         'date_effective' => $this->calculerDate($dateDebut, $config->ordre_affichage),
                        //     ]
                        // );
                    }
                }
            }

            DB::commit();
            //dd("Désignation enregistrée avec succès !");
            return back()->with('success', 'Planning global enregistré avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Calcule la date réelle basée sur le début de semaine et l'ordre (1=Ven, 2=Sam...)
     */
    private function calculerDate($dateDebut, $ordre)
    {
        return \Carbon\Carbon::parse($dateDebut)->addDays($ordre - 1);
    }

    public function update(Request $request, $id)
    {
        return $this->saveDesignation($request, $id);
    }

    protected function saveDesignation(Request $request, $id = null)
    {

        //dd($request->all());

        // 1. AJOUTER LA VALIDATION
        $validated = $request->validate([
            //'semaine_nom'         => 'required|string|max:255',
            'sous_departement_id' => 'required|exists:sous_departements,id',
            // On peut rendre la date requise ici si vous avez un champ date dans React
            'date_debut'          => 'required|date',
        ]);

        DB::transaction(function () use ($request, $id) {
            // 1. Créer ou trouver la désignation
            $designation = $id ? Designation::findOrFail($id) : new Designation();

            // Calcul automatique de la date de fin
            $dateDebut = Carbon::parse($request->date_debut);
            $dateFin   = $dateDebut->copy()->addDays(7);

            $designation->fill([
                'semaine_nom'         => $request->semaine_nom,
                'sous_departement_id' => $request->sous_departement_id,
                'createur_id'         => auth()->id(),
                // 2. REMPLIR LA DATE AUTOMATIQUEMENT SI ELLE MANQUE
                'date_debut'          => $dateDebut ?? now(),
                'date_fin'            => $dateFin, // Toujours calculée à partir de la date de début
                'statut'              => $request->statut ?? 'Brouillon',
            ])->save();

            // 2. Nettoyer les anciens items si on est en modification
            if ($id) {
                $designation->items()->delete();
            }

            //dd($request->affectations);

            // 3. Parcourir les laboratoires et les rôles envoyés par React
            foreach ($request->affectations as $labId => $roles) {
                foreach ($roles as $roleItem) {
                    if (! empty($roleItem['membres'])) {
                        // Pour chaque membre sélectionné dans le MultiSelect
                        foreach ($roleItem['membres'] as $membreId) {
                            $designation->items()->create([
                                'laboratoire_id' => $labId,
                                'role_tache_id'  => $roleItem['role_id'],
                                'membre_id'      => $membreId,  // On fournit l'ID ici
                                'date_effective' => $dateDebut, // Ou une autre logique pour la date effective
                            ]);
                        }
                        // 4. Attacher les membres dans la table pivot
                        // $item->membres()->sync($roleItem['membres']);
                    }
                }
            }
        });

        return redirect()->route('designations.index');
    }
    /**
     * Suppression (Optionnel)
     */
    public function destroy($id)
    {
        $designation = Designation::findOrFail($id);
        $designation->delete(); // Les items et pivots seront supprimés si cascadeOnDelete() est mis

        return redirect()->back()->with('message', 'Désignation supprimée.');
    }
}
