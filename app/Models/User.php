<?php

declare (strict_types = 1);

namespace App\Models;

use Carbon\Carbon;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

/**
 * Class User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    // Add HasFactory here

    protected $table = 'users';

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
        'name',
        'email',
        'email_verified_at',
        'password',
        'remember_token',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id'                => 'integer',
            'name'              => 'string',
            'email'             => 'string',
            'email_verified_at' => 'datetime',
            'password'          => 'string',
            'remember_token'    => 'string',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
            'is_admin'          => 'boolean', // Si vous avez une colonne is_admin
        ];
    }

    // app/Models/User.php

    public function isAdmin(): bool
    {
        // Option A: Si vous avez une colonne 'is_admin' (boolean)
        //return (bool) $this->is_admin;

        // Option B: Si vous utilisez Spatie Roles
        return $this->hasRole('admin');
    }

    public function isSuperAdmin(): bool
    {
        // Option A: Par email
        //return in_array($this->email, ['admin@votresite.com']);

        // Option B: Par un rôle spécifique
        return $this->hasRole('super_admin');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true; // ✅ Autorise tout en local le temps du débug
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_user', 'user_id', 'group_id');
    }

    /**
     * Vérifie si l'utilisateur possède une vue absolue (Admin ou Direction).
     */
    // public function hasAbsoluteView(): bool
    // {
    //     //dd($this); // Debug pour vérifier les données de l'utilisateur);
    //     $flag = $this->is_admin || $this->groups()->whereIn('code', ['admin'])->orWhere('name', 'Direction / Administration')->exists();
    //     dd($flag); // Debug pour vérifier le résultat de la vérification
    //     return $flag;
    // }
    public function hasAbsoluteView(): bool
    {
        // Option A : Si vous utilisez la colonne 'is_admin' ou les groupes custom
        return $this->is_admin || $this->groups()->where(function ($query) {
            $query->whereIn('code', ['admin'])
                ->orWhere('name', 'Direction / Administration');
        })->exists();
    }
    /**
     * Vérifie une permission globale basée sur les rôles/groupes de l'utilisateur.
     */
    public function hasGlobalPermission(string $module, string $action): bool
    {
        if ($this->hasAbsoluteView()) {
            return true;
        }

        $groupIds = $this->groups()->pluck('groups.id')->toArray();

        return DB::table('permissions_groupes')
            ->whereIn('group_id', $groupIds)
            ->where(['module_type' => $module, 'type_action' => $action])
            ->exists();
    }

    /**
     * Construit le dictionnaire complet des accès aux laboratoires [sous_departement_id => niveau_acces]
     */
    public function getLabAccessMap(): array
    {
        $groupIds = $this->groups()->pluck('groups.id')->toArray();

        return DB::table('group_sous_departement')
            ->whereIn('group_id', $groupIds)
            ->get()
            ->groupBy('sous_departement_id')
            ->map(function ($items) {
                $priorite = ['total' => 3, 'ecriture' => 2, 'lecture' => 1, 'aucune' => 0];
                return $items->sortByDesc(fn($item) => $priorite[$item->niveau_acces] ?? 0)->first()->niveau_acces;
            })
            ->toArray();
    }

    /**
     * Récupère le niveau d'accès spécifique pour un laboratoire donné.
     */
    public function getAccessLevelForLab(int $sousDepartementId): string
    {
        if ($this->hasAbsoluteView()) {
            return 'total'; // Un admin a tous les droits par défaut partout
        }

        $map = $this->getLabAccessMap();

        return $map[$sousDepartementId] ?? 'aucune';
    }

    public function sousDepartements(): BelongsToMany
    {
        return $this->belongsToMany(SousDepartement::class, 'sous_departement_user', 'user_id', 'sous_departement_id')
                    ->withPivot('can_create', 'can_read', 'can_update', 'can_delete')
                    ->withTimestamps();
    }
}
