<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoleTache extends Model
{
    /**
     * Nom de la table (optionnel si respecte la convention plurielle)
     */
    protected $table = 'role_taches';

    /**
     * Attributs assignables
     */
    protected $fillable = [
        'libelle',    // ex: "Responsable 1", "Vendredi"
        'categorie',  // ex: "responsable", "jour", "remplacant"
        'ordre',      // Pour le tri Ven -> Jeu
    ];

    /**
     * Relation avec les Requis (Configuration par Labo)
     */
    public function labRequis(): HasMany
    {
        return $this->hasMany(LabRequis::class, 'role_tache_id');
    }

    /**
     * Relation avec les Items (Lignes de désignation réelles)
     */
    public function designationItems(): HasMany
    {
        return $this->hasMany(DesignationItem::class, 'role_tache_id');
    }

    /**
     * Scope pour récupérer les jours dans l'ordre chronologique de votre cycle
     */
    public function scopeJours($query)
    {
        return $query->where('categorie', 'jour')->orderBy('ordre');
    }
    /**
     * Scope pour trier par défaut selon votre ordre logique
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('ordre')->orderBy('libelle');
    }
}
