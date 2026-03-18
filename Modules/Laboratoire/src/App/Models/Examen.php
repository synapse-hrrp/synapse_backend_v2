<?php

namespace Modules\Laboratoire\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Examen extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'examen_request_id',
        'agent_id',
        'status',
        'started_at',
        'finished_at',
        'validated_at',
        'observations',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'finished_at'  => 'datetime',
        'validated_at' => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────
    public function request(): BelongsTo
    {
        return $this->belongsTo(ExamenRequest::class, 'examen_request_id');
    }

    // Les résultats de cet examen
    public function resultats(): HasMany
    {
        return $this->hasMany(ExamenResultat::class);
    }

    // ── Scopes ────────────────────────────────────────────────
    public function scopeTermines($query)
    {
        return $query->where('status', 'termine');
    }

    public function scopeValides($query)
    {
        return $query->where('status', 'valide');
    }
}