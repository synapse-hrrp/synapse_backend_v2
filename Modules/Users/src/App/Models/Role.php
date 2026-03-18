<?php

namespace Modules\Users\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $table = 'roles';
    public $timestamps = false;

    protected $fillable = ['label', 'description'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_roles', 'roles_id', 'users_id');
    }

    public function fonctionnalites(): BelongsToMany
    {
        return $this->belongsToMany(Fonctionnalite::class, 'roles_fonctionnalites', 'roles_id', 'fonc_id')
            ->withTimestamps()
            ->withPivot('deleted_at')
            ->wherePivotNull('deleted_at');
    }
}
