<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Designation extends Model
{
    // On force le nom de la table ici


    protected $table = 'designations';

    protected $fillable = [
        //'laboratoire_id',
        'sous_departement_id', // Pour savoir à quel groupe appartient la semaine
        'date_debut',
        'date_fin',
        'semaine_nom',
        'statut',
        'notes_generales',
        'createur_id'
    ];

    protected $casts = [
        'date_debut' => 'date',
    ];

    // LA RELATION MANQUANTE :
    public function sousDepartement(): BelongsTo
    {
        return $this->belongsTo(SousDepartement::class, 'sous_departement_id');
    }
    // public function laboratoire(): BelongsTo
    // {
    //     return $this->belongsTo(Laboratoire::class);
    // }

    public function items(): HasMany
    {
        return $this->hasMany(DesignationItem::class, 'designation_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'createur_id');
    }

    /**
     * Logique de synchronisation des lignes du planning
     */
    public function syncItems(array $data): void
    {
        // 1. Nettoyage : On supprime les anciens items pour éviter les doublons
        // lors d'une modification (Edit)
        $this->items()->delete();

        //dd($data); // Debug : vérifier les données reçues du formulaire


        // 2. Traitement des 3 blocs de TableRepeaters
        // Les clés correspondent aux noms utilisés dans $set('nom_du_bloc')
        $sections = ['responsables_items', 'jours_items', 'remplacants_items'];

        foreach ($sections as $section) {
            if (isset($data[$section]) && is_array($data[$section])) {
                foreach ($data[$section] as $item) {

                    // On vérifie qu'il y a au moins un membre sélectionné
                    if (!empty($item['membres'])) {

                        // Puisque 'membres' est un array (Select multiple),
                        // on crée une ligne par membre désigné
                        foreach ($item['membres'] as $membreId) {
                            $this->items()->create([
                                'role_tache_id' => $item['role_tache_id'],
                                'membre_id' => $membreId,
                                'date_effective' => $this->date_debut, // ou logique de date spécifique
                            ]);
                        }
                    }
                }
            }
        }
    }


    protected function progressionStats(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Sécurité : si les données de base manquent
                if (!$this->sous_departement_id || !$this->date_debut) {
                    return ['labs' => [], 'total_global' => 0];
                }

                $laboratoires = \App\Models\Laboratoire::where('sous_departement_id', $this->sous_departement_id)->get();
                $stats = ['labs' => [], 'total_global' => 0];
                $totalRequis = 0;
                $totalSaisis = 0;

                foreach ($laboratoires as $lab) {
                    $requis = \App\Models\LabRequis::where('laboratoire_id', $lab->id)->sum('nombre_requis');

                    $saisis = \DB::table('designation_items')
                        ->where('designation_id', $this->id)
                        ->where('laboratoire_id', $lab->id)
                        ->count();

                    $totalRequis += $requis;
                    $totalSaisis += $saisis;

                    $stats['labs'][] = (object)[
                        'nom' => $lab->nom,
                        'percentage' => $requis > 0 ? min(round(($saisis / $requis) * 100), 100) : 0,
                        'saisis' => $saisis,
                        'total' => $requis,
                    ];
                }

                $stats['total_global'] = $totalRequis > 0 ? round(($totalSaisis / $totalRequis) * 100) : 0;
                $stats['nom_secteur'] = $this->sousDepartement?->nom ?? 'Secteur';

                return $stats;
            }
        );
    }


}
