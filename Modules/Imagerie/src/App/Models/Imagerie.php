<?php

namespace Modules\Imagerie\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Imagerie extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'imagerie_request_id',
        'agent_id',
        'status',
        'appareil',
        'salle',
        'produit_contraste',
        'type_contraste',
        'incidents',
        'details_incidents',
        'started_at',
        'finished_at',
        'validated_at',
        'observations',
    ];

    protected $casts = [
        'produit_contraste' => 'boolean',
        'incidents'         => 'boolean',
        'started_at'        => 'datetime',
        'finished_at'       => 'datetime',
        'validated_at'      => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────
    public function request(): BelongsTo
    {
        return $this->belongsTo(ImagerieRequest::class, 'imagerie_request_id');
    }

    public function resultat(): HasOne
    {
        return $this->hasOne(ImagerieResultat::class);
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