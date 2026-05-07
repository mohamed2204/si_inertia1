<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaboratoireConfigRequis extends Model
{
    use HasFactory;

    protected $table = 'laboratoire_config_requis'; // Facultatif si le nom suit les conventions

    protected $fillable = [
        'lab_config_id',
        'libelle',
        'ordre',
        'is_obligatoire'
    ];

    // Relation inverse : un requis appartient à une config de labo
    public function config()
    {
        return $this->belongsTo(LaboratoireConfig::class, 'lab_config_id');
    }
}