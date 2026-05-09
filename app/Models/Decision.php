<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Decision extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'libelle',
        'date_decision',
        'date_effet',
        'date_expiration',
        'commentaires',
        'user_id'
    ];

    /**
     * Relation vers les Désignations (Enfants)
     * Une décision fédère plusieurs désignations hebdomadaires.
     */
    public function designations(): HasMany
    {
        // On lie bien ici au modèle Designation (Parent de la semaine)
        return $this->hasMany(Designation::class, 'decision_id');
    }

    /**
     * L'auteur de la décision (Super-Admin)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}