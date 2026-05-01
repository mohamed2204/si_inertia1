<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;


class SousDepartement extends Model
{
    //use HasFactory;

    // Si votre table s'appelle "sous_departements" (par défaut),
    // Laravel la trouvera. Sinon, spécifiez-la :
    protected $table = 'sous_departements';

    protected $fillable = ['nom', 'departement_id'];

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_sous_departement');
    }

    public function laboratoires(): HasMany
    {
        return $this->hasMany(Laboratoire::class);
    }
    
    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($sousDepartement) {
            $group = \App\Models\Group::create([
                'name' => 'Équipe ' . $sousDepartement->nom,
            ]);
            $group->sousDepartements()->attach($sousDepartement->id);
        });
    }

}
