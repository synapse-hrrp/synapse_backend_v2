<?php

namespace Modules\Soins\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hospitalisation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'hospitalisation_request_id',
        'agent_id',
        'status',
        'service',
        'chambre',
        'lit',
        'diagnostic_admission',
        'code_cim10',
        'diagnostic_sortie',
        'mode_sortie',
        'admission_at',
        'sortie_at',
        'observations',
    ];

    protected $casts = [
        'admission_at' => 'datetime',
        'sortie_at'    => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────
    public function request(): BelongsTo
    {
        return $this->belongsTo(HospitalisationRequest::class, 'hospitalisation_request_id');
    }

    // ── Helpers ───────────────────────────────────────────────
    // Durée du séjour en jours
    public function getDureeSejourAttribute(): ?int
    {
        if ($this->admission_at && $this->sortie_at) {
            return $this->admission_at->diffInDays($this->sortie_at);
        }
        return null;
    }
}