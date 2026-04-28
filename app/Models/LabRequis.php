<?php
namespace App\Models;

use App\Enums\SectionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabRequis extends Model
{
    /**
     * La table associée au modèle.
     */
    protected $table = 'lab_requis';

    /**
     * Les attributs assignables en masse.
     */
    protected $fillable = [
        'laboratoire_id',
        'role_tache_id',
        'nombre_requis',
        'section', // 'responsables', 'jours', 'remplacants'
        'ordre',
    ];

    protected $casts = [
        'section' => SectionType::class,
    ];
    /**
     * Relation avec le Laboratoire (Niveau 3)
     */
    public function laboratoire(): BelongsTo
    {
        return $this->belongsTo(Laboratoire::class);
    }

    /**
     * Relation avec le Dictionnaire des Rôles (ex: Responsable 1, Vendredi...)
     */
    public function roleTache(): BelongsTo
    {
        return $this->belongsTo(RoleTache::class, 'role_tache_id');
    }

    /**
     * Scope pour filtrer par section facilement dans vos ressources
     */
    public function scopeInSection($query, string $section)
    {
        return $query->where('section', $section)->orderBy('ordre');
    }
}
