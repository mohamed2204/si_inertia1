<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignationItem extends Model
{

    protected $fillable = [
        'designation_id',
        'laboratoire_id',        // <--- C'est maintenant la clé de voûte
        'laboratoire_config_id', // <--- Remplace config_jour_id, config_responsable_id, config_remplacant_id
                                 //'role_tache_id',   // <--- C'est lui le remplaçant de tache_id, config_jour_id, etc.
        'membre_id',
        'date_effective',
        'observations',
    ];

    /**
     * Sérialisation automatique des attributs
     */
    protected $casts = [
        // Indique à Laravel de convertir n'importe quel format de date reçu
        // en chaîne Y-M-D standard avant l'envoi à MySQL
        'date_effective' => 'date:Y-m-d',
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
    public function configuration()
    {
        return $this->belongsTo(LaboratoireConfig::class, 'laboratoire_config_id');
    }

    public function laboratoire(): BelongsTo
    {
        return $this->belongsTo(Laboratoire::class);
    }
}
