<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departement extends Model
{
    protected $fillable = ['nom'];

    /**
     * Un département possède plusieurs sous-départements (les 9 labs).
     */
    public function sousDepartements(): HasMany
    {
        return $this->hasMany(SousDepartement::class);
    }
}
