<?php

namespace App\Filament\Resources\DesignationResource\Pages;

use App\Filament\Resources\DesignationResource;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateDesignation extends CreateRecord
{
    protected static string $resource = DesignationResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Création de l'en-tête avec TOUS les champs nécessaires
        // Calcul automatique : date_fin = date_debut + 6 jours
        $dateDebut = Carbon::parse($data['date_debut']);
        $dateFin = $dateDebut->copy()->addDays(6);


        $exists = \App\Models\Designation::where('laboratoire_id', $this->data['laboratoire_id'])
            ->where('date_debut', $this->data['date_debut'])
            ->exists();

        if ($exists) {
            Notification::make()
                ->danger()
                ->title('Doublon détecté')
                ->body('Ce laboratoire possède déjà un planning pour cette semaine.')
                ->send();

            $this->halt(); // Arrête le processus de création
        }

        //dd($data); // Debug : vérifier les données reçues du formulaire

        $record = static::getModel()::create([
            //'sous_departement_id' => $data['sous_departement_id'],
            'laboratoire_id' => $data['laboratoire_id'],
            'date_debut' => $data['date_debut'],
            'date_fin' => $dateFin, // <--- La solution est ici
            'statut' => 'brouillon',
            'est_ouvert' => true,
            'createur_id' => auth()->id(),
        ]);


        // 3. SAUVEGARDE DES ITEMS (L'étape manquante)
        // Cette méthode doit exister dans votre modèle app/Models/Designation.php
        //$record->syncItems($data);

        if ($record instanceof \App\Models\Designation) {
            $record->syncItems($data);
        }

        return $record;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()->success()->title('Désignation enregistrée')->body('La grille de la semaine a été mise à jour avec succès.')->icon('heroicon-o-check-circle')->send();
    }
}
