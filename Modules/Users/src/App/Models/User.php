<?php

namespace Modules\Users\App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes, HasFactory;

     protected static function newFactory()
    {
        return \Modules\Users\Database\Factories\UserFactory::new();
    }

    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password', 'agent_id'];

    protected $hidden = ['password', 'remember_token', 'pin'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'users_roles', 'users_id', 'roles_id')
            ->withTimestamps();
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(\Modules\Users\App\Models\Agent::class, 'agent_id', 'id');
    }

    /**
     * Retourne toutes les fonctionnalités (permissions) de l'utilisateur via ses rôles.
     */
    public function permissionsList()
    {
        // Évite N+1 : si roles pas chargés, on charge avec fonctionnalites
        if (!$this->relationLoaded('roles')) {
            $this->load('roles.fonctionnalites');
        }

        return $this->roles->map->fonctionnalites->flatten();
    }

    /**
     * Retourne la liste des tech_label (ex: "patients.create")
     */
    public function permissionsListStrings(): array
    {
        return $this->permissionsList()
            ->pluck('tech_label')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Cache permissions pour éviter de requêter à chaque fois.
     */
    public function cachedPermissions(int $minutes = 10): array
    {
        return cache()->remember(
            "user:{$this->id}:perms",
            now()->addMinutes($minutes),
            fn () => $this->permissionsListStrings()
        );
    }

    /**
     * True si l'utilisateur a la permission (via fonctionnalites.tech_label)
     */
    public function hasPermission(string $techLabel): bool
    {
        return in_array($techLabel, $this->cachedPermissions(), true);
    }

    /**
     * True si l'utilisateur a un rôle (via roles.label)
     */
    public function hasRole(string $label): bool
    {
        return $this->roles()->where('label', $label)->exists();
    }

    /**
     * À appeler quand tu modifies ses rôles / permissions.
     */
    public function flushPermissionsCache(): void
    {
        cache()->forget("user:{$this->id}:perms");
    }
}
