<?php

namespace Modules\Soins\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Accouchement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'accouchement_request_id',
        'agent_id',
        'status',
        'type_accouchement',
        'nombre_nouveau_nes',
        'poids_naissance',
        'apgar_1min',
        'apgar_5min',
        'sexe_nouveau_ne',
        'terme_semaines',
        'complications',
        'details_complications',
        'debut_travail_at',
        'naissance_at',
        'finished_at',
        'observations',
    ];

    protected $casts = [
        'complications'    => 'boolean',
        'debut_travail_at' => 'datetime',
        'naissance_at'     => 'datetime',
        'finished_at'      => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────
    public function request(): BelongsTo
    {
        return $this->belongsTo(AccouchementRequest::class, 'accouchement_request_id');
    }

    // Déclaration de naissance liée
    public function declarationNaissance(): HasOne
    {
        return $this->hasOne(DeclarationNaissance::class);
    }
}