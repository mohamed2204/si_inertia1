<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Laboratoire extends Model
{
    protected $fillable = ['nom', 'code', 'est_actif', 'sous_departement_id'];

    public function config_jours()
    {
        return $this->hasMany(LaboratoireConfig::class)->orderBy('ordre_affichage');
    }

    public function sousDepartement(): BelongsTo
    {
        return $this->belongsTo(SousDepartement::class);
    }

    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class);
    }

    // Dans LaboratoireConfig.php
    public function requis()
    {
        // Cette relation lie le jour (ex: Lundi) aux postes nécessaires (ex: 1 Technicien)
        return $this->hasMany(LaboratoireConfigRequis::class, 'laboratoire_config_id');
    }
}
