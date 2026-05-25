<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Grade
 *
 * @property int $id
 * @property string $nom
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection|Promotion[] $elevesPromotions
 * @property Collection|Specialite[] $elevesSpecialites
 * @property Collection|eleve[] $eleves
 */
class Grade extends Model
{
    protected $table = 'grades';

    // use SoftDeletes; // Use the trait in the model
    /**
     * @var string
     */
    protected $connection = 'mysql';

    protected $primaryKey = 'id';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'nom',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'nom' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Eleve, $this>
     */
    public function eleves(): HasMany
    {
        return $this->hasMany(Eleve::class, 'grade_id', 'id');
    }

    /**
     * @return BelongsToMany<Promotion, $this>
     */
    public function elevesPromotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'eleves', 'id', 'id')
            ->withPivot('grade_id', 'promotion_id', 'specialite_id', 'nom', 'prenom', 'matricule', 'nom_arabe', 'prenom_arabe', 'date_naissance', 'sexe', 'cni', 'nationalite', 'photo')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Specialite, $this>
     */
    public function elevesSpecialites(): BelongsToMany
    {
        return $this->belongsToMany(Specialite::class, 'eleves', 'id', 'id')
            ->withPivot('grade_id', 'promotion_id', 'specialite_id', 'nom', 'prenom', 'matricule', 'nom_arabe', 'prenom_arabe', 'date_naissance', 'sexe', 'cni', 'nationalite', 'photo')
            ->withTimestamps();
    }
}
