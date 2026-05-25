<?php
namespace App\Filament\Resources\DesignationResource\Pages;

use App\Filament\Resources\DesignationResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDesignation extends EditRecord
{
    protected static string $resource = DesignationResource::class;

    /**
     * S'exécute après que le formulaire a été hydraté
     */
    protected function afterFill(): void
    {
        //dump($this->form->getRawState());

        $data = $this->form->getRawState();

        // 1. On récupère le sous-département (qui pilote l'affichage des onglets)
        $sousDeptId = $data['sous_departement_id'] ?? null;

        if ($sousDeptId) {
            // 2. On récupère tous les laboratoires concernés
            $labs = \App\Models\Laboratoire::where('sous_departement_id', $sousDeptId)->get();

            $updates = [];
            foreach ($labs as $lab) {
                $key = 'items_lab_' . $lab->id;

                // Si la clé existe dans $data mais que le Repeater est vide à l'écran
                // On force la ré-injection pour que Filament génère ses UUID
                if (!empty($data[$key])) {
                    $updates[$key] = $data[$key];
                }
            }

            // 3. On "re-remplit" le formulaire avec les données consolidées
            if (!empty($updates)) {
                $this->form->fill([
                    ...$data,
                    ...$updates
                ]);
            }
        }
    }
    // Remplissage du formulaire au chargement (Mount)
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $designation = $this->getRecord();
        // On charge tout d'un coup pour éviter les requêtes SQL en boucle (N+1)
        $designation->load(['laboratoire.sousDepartement', 'items.roleTache']);

        $sousDept = $designation->laboratoire?->sousDepartement;

        // 1. On assure la sélection des menus déroulants
        $data['departement_id']      = $sousDept?->departement_id;
        $data['sous_departement_id'] = $sousDept?->id;

        // Dans votre boucle ou méthode de chargement
        $laboIdDeLaDesignation = (int) $designation->laboratoire_id;

        // On récupère tous les items de la désignation, groupés par rôle
        $itemsGroupesParRole = $designation->items->groupBy('role_tache_id');

//        $itemsGroupesParRole = $designation->items
//            ->filter(fn($item) => (int)$item->designation_id === (int)$designation->id)
//            ->groupBy('role_tache_id');


        // 2. On récupère TOUS les laboratoires de ce secteur
        $labs = \App\Models\Laboratoire::where('sous_departement_id', $sousDept?->id)->get();
        //dd($tousLesLabs);

        // 3. BOUCLE CRUCIALE : On pré-remplit chaque onglet
        foreach ($labs as $lab) {
            $currentLabId = (int) $lab->id;

            $requis = \App\Models\LabRequis::where('laboratoire_id', $currentLabId)
                ->with('roleTache')
                ->get();

            // Dans votre boucle foreach ($tousLesLabs as $lab)
            $itemsEnregistresDuLab = $designation->items
                ->where('laboratoire_id', $currentLabId) // Simple, rapide, efficace
                ->groupBy('role_tache_id');

            dump($lab->nom);
            dump($itemsEnregistresDuLab);

            // Déterminer la source des items (Données DB ou collection vide)
            $sourceItems = ($currentLabId === $laboIdDeLaDesignation)
                ? $itemsGroupesParRole
                : collect();

            // SI le labo de l'onglet est celui de la désignation, on affiche les données
            if ($currentLabId === $laboIdDeLaDesignation) {
                // On fusionne et on FORCE la réindexation
                $data['items_lab_' . $currentLabId] = array_values(array_merge(
                    $this->mergeRequisAndItems($requis, $itemsEnregistresDuLab, 'responsables', $designation->date_debut),
                    $this->mergeRequisAndItems($requis, $itemsEnregistresDuLab, 'jour', $designation->date_debut),
                    $this->mergeRequisAndItems($requis, $itemsEnregistresDuLab, 'remplacants', $designation->date_debut)
                ));
            }
            // SINON, on affiche juste la structure vide (les requis) pour les autres labos
            else {
                $data['items_lab_' . $currentLabId] = array_merge(
                    $this->mergeRequisAndItems($requis, collect(), 'responsables', $designation->date_debut),
                    $this->mergeRequisAndItems($requis, collect(), 'jour', $designation->date_debut),
                    $this->mergeRequisAndItems($requis, collect(), 'remplacants', $designation->date_debut)
                );
            }
        }
//        foreach ($tousLesLabs as $lab) {
//            $key = 'items_lab_' . $lab->id;
//
//            // On récupère les requis théoriques pour ce labo précis
//            $requisDuLab = \App\Models\LabRequis::where('laboratoire_id', $lab->id)
//                ->with('roleTache')
//                ->get();
//            //dd($requisDuLab);
//            // On filtre les items déjà sauvés en DB qui appartiennent à ce labo
//            // Filtrage sécurisé en mémoire
//            $itemsEnregistresDuLab = $designation->items
//                ->filter(function ($item) use ($lab, $designation) {
////                    \Illuminate\Log\log('Designation .' . $designation->id);
////                    \Illuminate\Log\log('Labo .' . $lab->id);
//                    \Illuminate\Log\log('Item designation #' . $item->designation);
//                    \Illuminate\Log\log('Item #' . $item);
//
//                    return (int) $item->designation->laboratoire_id === (int) $lab->id;
//                })
//                ->groupBy('role_tache_id');
//
//            //Log::info($itemsEnregistresDuLab);
//
//            // On fusionne et on injecte dans le tableau global $data
//            // Même si l'user ne clique pas sur l'onglet, la donnée est là, prête à être sauvée
//            $data[$key] = array_merge(
//                $this->mergeRequisAndItems($requisDuLab, $itemsEnregistresDuLab, 'responsable'),
//                $this->mergeRequisAndItems($requisDuLab, $itemsEnregistresDuLab, 'jour'),
//                $this->mergeRequisAndItems($requisDuLab, $itemsEnregistresDuLab, 'remplacant')
//            );
//        }

        dump($data);
        return $data;
    }
//    public function mergeRequisAndItems($requis, $itemsEnregistres, $section, $dateDebut)
//    {
//        $dateBase = \Carbon\Carbon::parse($dateDebut);
//
//        $items = $requis->where('section', $section)->map(function ($req, $index) use ($itemsEnregistres, $dateBase) {
//            // Calcul du J+N : on ajoute des jours selon l'ordre ou une logique de libellé
//            // Si vos requis sont ordonnés du Vendredi au Jeudi :
//            // Index 0 = Vendredi (J+0), Index 1 = Samedi (J+1), etc.
//            $dateLigne = $dateBase->copy()->addDays($index);
//
//            return [
//                'role_tache_id' => $req->role_tache_id,
//                'role_libelle'  => $req->roleTache?->libelle,
//                'nombre_requis' => $req->nombre_requis,
//                'section'       => $req->section,
//
//                // On stocke la date calculée pour l'affichage et la sauvegarde
//                'date_effective' => $dateLigne->format('Y-m-d'),
//                'date_display'   => $dateLigne->translatedFormat('l d F'), // ex: "samedi 25 avril"
//
//                'membres' => $itemsEnregistres->get($req->role_tache_id)?->pluck('membre_id')->toArray() ?? [],
//            ];
//        })->toArray();
//
//        dump($items);
//
//        return $items;
//    }

    public function mergeRequisAndItems($requis, $itemsEnregistres, $section, $dateDebut)
    {
        $dateBase = \Carbon\Carbon::parse($dateDebut);

//        dump($requis);
//        dump($section);
//        dump($itemsEnregistres);
//        dump($dateBase);

        // 1. On filtre avec filter() pour éviter les problèmes de type
        // 2. On ajoute values() pour réinitialiser les index à 0, 1, 2...
        $items = $requis->filter(function ($req) use ($section) {
            return $req->section === $section;
        })->values()->map(function ($req, $index) use ($itemsEnregistres, $dateBase, $section) {

            // Logique J+N uniquement pour la section 'jour'
            $dateLigne = ($section === 'jour')
                ? $dateBase->copy()->addDays($index)
                : $dateBase->copy();

            return [
                'role_tache_id' => $req->role_tache_id,
                'role_libelle'  => $req->roleTache?->libelle ?? 'Rôle inconnu',
                'nombre_requis' => $req->nombre_requis,
                'section'       => $req->section,
                'date_effective' => $dateLigne->format('Y-m-d'),
                'date_display'   => $dateLigne->translatedFormat('l d F'),

                // On s'assure que $itemsEnregistres est bien groupé par role_tache_id
                'membres' => $itemsEnregistres->get($req->role_tache_id)?->pluck('membre_id')->toArray() ?? [],
            ];
        })->toArray();

        dump($items);

        return $items;
    }

//    private function mergeRequisAndItems($allRequis, $itemsEnregistres, $categorie): array
//    {
//
//        //dump($Items);
//         return $allRequis->filter(fn($req) => $req->roleTache?->categorie === $categorie)
//            ->map(function ($req) use ($categorie, $itemsEnregistres) {
//
//                // 1. On force la clé en entier pour être sûr de la correspondance
//                $roleId = (int) $req->role_tache_id;
//
//                // 2. On cherche dans la collection groupée (en gérant les index numériques ou string)
//                $dbItems = $itemsEnregistres->get($roleId) ?? $itemsEnregistres->get((string) $roleId);
//                //dump($dbItems);
//                // 3. On extrait les IDs des membres proprement
//                // Dans votre méthode mergeRequisAndItems
//                $membresIds = $dbItems
//                    ? $dbItems->pluck('membre_id')
//                        ->map(fn($id) => (string) $id) // <--- FORCE LE STRING ICI
//                        ->toArray()
//                    : [];
//                //dump($membresIds);
//                return [
//                    'role_tache_id' => $roleId,
//                    'role_libelle'  => $req->roleTache?->libelle,
//                    'nombre_requis' => $req->nombre_requis, // Assurez-vous que ce champ existe
//                    'section'       => $categorie,
//                    'membres'       => $membresIds,
//                ];
//            })
//            ->values()
//            ->toArray();
//    }
//    /**
//     * Fusionne la structure des requis avec les données de la DB
//     */
////    private function mergeRequisAndItems($allRequis, $itemsEnregistres, $categorie): array
////    {
////        return $allRequis->filter(fn($req) => $req->roleTache->categorie === $categorie)
////            ->map(function ($req) use ($itemsEnregistres) {
////                // On cherche si on a déjà des membres pour ce rôle précis
////                $dbItems = $itemsEnregistres->get($req->role_tache_id);
//////                return [
//////                    'role_tache_id' => $req->role_tache_id,
//////                    'role_libelle'  => $req->roleTache->libelle,
//////                    // Si on a des données en DB on les met, sinon tableau vide pour le select
//////                    'membres'       => $dbItems ? $dbItems->pluck('membre_id')->toArray() : [],
//////                ];
////                // Dans votre méthode mergeRequisAndItems
////                $membresIds = isset($itemsEnregistres[$req->role_tache_id])
////                    ? $itemsEnregistres[$req->role_tache_id]->pluck('membre_id')->toArray()
////                    : [];
////
////                return [
////                    'role_tache_id' => $req->role_tache_id,
////                    'role_libelle' => $req->roleTache->libelle,
////                    'membres' => $membresIds, // Doit être un array d'IDs pour le Select multiple
////                ];
////            })
////            ->values()
////            ->toArray();
////    }

    // Sauvegarde des modifications
    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {

        // On ne passe que les champs qui existent réellement en table 'designations'
        $record->update([
            'laboratoire_id' => $data['laboratoire_id'],
            'date_debut'     => $data['date_debut'],
            'date_fin'       => \Carbon\Carbon::parse($data['date_debut'])->addDays(6),
        ]);

        // Au lieu de $record->syncItems($data);
        if ($record instanceof \App\Models\Designation) {
            $record->syncItems($data);
        }
        // On relance la synchro pour mettre à jour les lignes du planning
        //$record->syncItems($data);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()->success()->title('Désignation enregistrée')->body('La grille de la semaine a été mise à jour avec succès.')->icon('heroicon-o-check-circle')->send();
    }


}
