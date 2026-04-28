<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Laboratoire extends Model
{
    protected $fillable =  ['nom', 'code', 'est_actif', 'sous_departement_id'];

    /**
     * Récupère tous les requis associés à ce laboratoire.
     * On trie par 'ordre' par défaut pour que le Drag & Drop soit respecté partout.
     */
    public function labRequis(): HasMany
    {
        return $this->hasMany(LabRequis::class, 'laboratoire_id')
                    ->orderBy('ordre', 'asc');
    }

    public function sousDepartement(): BelongsTo
    {
        return $this->belongsTo(SousDepartement::class);
    }

    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class);
    }
}
