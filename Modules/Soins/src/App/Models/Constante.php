<?php

namespace Modules\Soins\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Constante extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'agent_id',
        'tension_systolique',
        'tension_diastolique',
        'frequence_cardiaque',
        'frequence_respiratoire',
        'temperature',
        'poids',
        'taille',
        'imc',
        'saturation_o2',
        'glycemie',
        'observations',
        'pris_le',
    ];

    protected $casts = [
        'temperature' => 'decimal:1',
        'poids'       => 'decimal:2',
        'taille'      => 'decimal:1',
        'imc'         => 'decimal:1',
        'glycemie'    => 'decimal:2',
        'pris_le'     => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }
}