<?php

namespace Modules\Soins\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ActeOperatoireRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'registre_id',
        'tariff_item_id',
        'unit_price_applied',
        'billing_request_id',
        'status',
        'authorized_at',
        'completed_at',
        'type_operation',
        'indication',
        'is_urgent',
        'agent_id',
        'date_prevue',
    ];

    protected $casts = [
        'unit_price_applied' => 'decimal:2',
        'is_urgent'          => 'boolean',
        'authorized_at'      => 'datetime',
        'completed_at'       => 'datetime',
        'date_prevue'        => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────
    public function acteOperatoire(): HasOne
    {
        return $this->hasOne(ActeOperatoire::class);
    }

    // ── Scopes ────────────────────────────────────────────────
    public function scopeAutorises($query)
    {
        return $query->where('status', 'authorized');
    }

    public function estAutorise(): bool
    {
        return $this->status === 'authorized';
    }

    public function scopeEnAttente($query)
    {
        return $query->where('status', 'pending_payment');
    }
}