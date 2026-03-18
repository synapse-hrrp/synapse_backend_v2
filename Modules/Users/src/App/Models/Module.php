<?php

namespace Modules\Users\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $table = 'modules';
    public $timestamps = false;

    protected $fillable = ['label', 'description'];

    public function fonctionnalites(): HasMany
    {
        return $this->hasMany(Fonctionnalite::class, 'modules_id', 'id');
    }
}
