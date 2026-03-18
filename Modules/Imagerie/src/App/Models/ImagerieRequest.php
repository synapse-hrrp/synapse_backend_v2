<?php

namespace Modules\Imagerie\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ImagerieRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'registre_id',
        'imagerie_type_id',
        'tariff_item_id',
        'unit_price_applied',
        'billing_request_id',
        'status',
        'authorized_at',
        'completed_at',
        'region_anatomique',
        'renseignements_cliniques',
        'is_urgent',
        'agent_id',
    ];

    protected $casts = [
        'unit_price_applied' => 'decimal:2',
        'is_urgent'          => 'boolean',
        'authorized_at'      => 'datetime',
        'completed_at'       => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────
    public function imagerieType(): BelongsTo
    {
        return $this->belongsTo(ImagerieType::class);
    }

    public function imagerie(): HasOne
    {
        return $this->hasOne(Imagerie::class);
    }

    // ── Scopes ────────────────────────────────────────────────
    // Worklist Imagerie — uniquement les demandes autorisées
    public function scopeAutorises($query)
    {
        return $query->where('status', 'authorized');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('status', 'pending_payment');
    }

    // ── Helpers ───────────────────────────────────────────────
    public function estAutorise(): bool
    {
        return $this->status === 'authorized';
    }

    public function estEnAttentePaiement(): bool
    {
        return $this->status === 'pending_payment';
    }
}