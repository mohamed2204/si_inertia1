<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
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
            'id' => 'integer',
            'name' => 'string',
            'email' => 'string',
            'email_verified_at' => 'datetime',
            'password' => 'string',
            'remember_token' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
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

//    public function canAccessPanel(Panel $panel): bool
//    {
//        // L'admin doit pouvoir entrer, sinon redirection infinie
//        return $this->hasRole(['admin', 'super_admin']);
//    }

//    public function roles(): BelongsToMany
//    {
//        return $this->belongsToMany(\Spatie\Permission\Models\Role::class);
//    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

//    public function hasRole($name): bool
//    {
//        return $this->roles->contains('name', $name);
//    }
//
//    public function hasAnyRole(array $names): bool
//    {
//        return $this->roles->whereIn('name', $names)->isNotEmpty();
//    }
}
