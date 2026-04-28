<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Group extends Model
{
    protected $fillable = [
        'name',
        'code',
    ];

    /**
     * Boot function pour générer le code automatiquement si vide
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($group) {
            if (empty($group->code)) {
                $group->code = 'GRP-' . Str::upper(Str::slug($group->name));
            }
        });
    }

    /**
     * Les utilisateurs qui appartiennent à ce groupe
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Les sous-départements gérés par ce groupe
     */
    public function sousDepartements(): BelongsToMany
    {
        // On précise le nom de la table pivot créée précédemment
        return $this->belongsToMany(SousDepartement::class, 'group_sous_departement');
    }

    /**
     * Helper pour récupérer tous les laboratoires liés à ce groupe
     * (Via les sous-départements)
     */
    public function laboratoires(): Collection
    {
        // Permet de remonter la chaîne : Groupe -> Sous-Départements -> Laboratoires
        return $this->sousDepartements()->with('laboratoires')->get()->pluck('laboratoires')->flatten();
    }
}
