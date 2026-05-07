<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaboratoireConfig extends Model
{
    use HasFactory;

    protected $fillable = ['laboratoire_id', 'jour', 'jour_label', 'ordre_affichage'];

    /**
     * Relation avec les libellés de postes (Requis)
     * C'est cette relation qui alimente les selects de votre interface
     */
    public function requis()
    {
        return $this->hasMany(LaboratoireConfigRequis::class, 'lab_config_id')->orderBy('ordre');
    }

    /**
     * Relation avec le laboratoire parent
     */
    public function laboratoire()
    {
        return $this->belongsTo(Laboratoire::class);
    }
}