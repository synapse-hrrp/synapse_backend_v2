<?php

namespace Modules\Soins\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Consultation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'consultation_request_id',
        'agent_id',
        'status',
        'anamnese',
        'examen_clinique',
        'diagnostic',
        'code_cim10',
        'conclusion',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────
    public function request(): BelongsTo
    {
        return $this->belongsTo(ConsultationRequest::class, 'consultation_request_id');
    }

    // Les constantes prises pendant la consultation
    public function constantes(): HasMany
    {
        return $this->hasMany(Constante::class);
    }

    // Les prescriptions de la consultation
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }
}