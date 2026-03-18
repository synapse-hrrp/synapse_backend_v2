<?php

namespace Modules\Imagerie\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImagerieType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nom',
        'code',
        'categorie',
        'delai_heures',
        'preparation',
        'contre_indications',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    // ── Relations ──────────────────────────────────────────────
    public function imagerieRequests(): HasMany
    {
        return $this->hasMany(ImagerieRequest::class);
    }

    public function scopeActifs($query)
    {
        return $query->where('active', true);
    }
}