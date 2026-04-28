<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignationItem extends Model
{

    protected $fillable = [
        'designation_id',
        'laboratoire_id', // <--- C'est maintenant la clé de voûte
        'role_tache_id',   // <--- C'est lui le remplaçant de tache_id, config_jour_id, etc.
        'membre_id',
        'date_effective',
        'observations',
        // 'laboratoire_id', // Optionnel : on peut le retrouver via la désignation parente
    ];

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    //    public function laboratoire(): BelongsTo
//    {
//        return $this->belongsTo(Laboratoire::class);
//    }

    public function membre(): BelongsTo
    {
        return $this->belongsTo(Membre::class);
    }

    /**
     * La relation unique qui remplace ConfigJour, ConfigResponsable et ConfigRemplacant
     */
    public function roleTache(): BelongsTo
    {
        // C'est ici que tout se joue désormais
        return $this->belongsTo(RoleTache::class, 'role_tache_id');
    }
}
