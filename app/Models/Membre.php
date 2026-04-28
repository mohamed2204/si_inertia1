<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Membre extends Model
{
    //public $timestamps = false; // À adapter selon votre SQL

    protected $fillable = [
        'nom',
        'matricule',
        'departement_id',
        'email',
        'prenom',
        'est_actif',
    ];

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }
}
