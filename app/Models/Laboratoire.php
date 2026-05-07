<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Laboratoire extends Model
{
    protected $fillable = ['nom', 'code', 'est_actif', 'sous_departement_id'];

    // App\Models\Laboratoire.php

    // Indique à Laravel d'inclure 'config_jours' dans le JSON envoyé à React
    //protected $appends = ['config_jours'];

    // public function labRequis()
    // {
    //     return $this->hasMany(LabRequis::class, 'laboratoire_id')
    //         ->orderBy('ordre', 'asc');
    // }

    // À AJOUTER (Nouvelle structure) :
    public function config_jours()
    {
        return $this->hasMany(LaboratoireConfig::class)->orderBy('ordre_affichage');
    }
    // public function getConfigJoursAttribute()
    // {
    //     // On récupère les requis et on les groupe par 'section' (qui semble être vos jours/remplaçants)
    //     return $this->labRequis->groupBy('section')->map(function ($items, $section) {
    //         return [
    //             'jour'       => $section, // Ex: 'Jeudi', 'Ven', 'Remp 1'
    //             'jour_label' => ucfirst($section),
    //             'quota'      => $items->sum('nombre_requis'), // Somme des effectifs pour cette section
    //             'details'    => $items->map(function ($item) {
    //                 return [
    //                     'role' => $item->roleTache->libelle, // Charge le nom du rôle (Chef, Tech, etc.)
    //                     'nb'   => $item->nombre_requis,
    //                 ];
    //             }),
    //         ];
    //     })->values();
    // }
    /**
     * Récupère tous les requis associés à ce laboratoire.
     * On trie par 'ordre' par défaut pour que le Drag & Drop soit respecté partout.
     */
    // public function labRequis(): HasMany
    // {
    //     return $this->hasMany(LabRequis::class, 'laboratoire_id')
    //         ->orderBy('ordre', 'asc');
    // }

    public function sousDepartement(): BelongsTo
    {
        return $this->belongsTo(SousDepartement::class);
    }

    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class);
    }
}
