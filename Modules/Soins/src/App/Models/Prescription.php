<?php

namespace Modules\Soins\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'consultation_id',
        'agent_id',
        'status',
        'instructions_generales',
        'valide_jusqu_au',
        'renouvelable',
        'nombre_renouvellements',
        'emise_le',
    ];

    protected $casts = [
        'renouvelable'           => 'boolean',
        'valide_jusqu_au'        => 'date',
        'emise_le'               => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    // Les médicaments de la prescription
    public function lignes(): HasMany
    {
        return $this->hasMany(PrescriptionLigne::class);
    }
}