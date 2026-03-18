<?php

namespace Modules\Users\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fonctionnalite extends Model
{
    protected $table = 'fonctionnalites';

    protected $fillable = ['label', 'tech_label', 'modules_id', 'parent'];

    protected $hidden = ['created_at', 'updated_at', 'modules_id', 'pivot'];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'modules_id', 'id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Fonctionnalite::class, 'parent', 'id');
    }

    public function enfants(): HasMany
    {
        return $this->hasMany(Fonctionnalite::class, 'parent', 'id');
    }
}
