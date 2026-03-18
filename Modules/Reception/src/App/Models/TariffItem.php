<?php

namespace Modules\Reception\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TariffItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tariff_plan_id',
        'billable_service_id',
        'prix_unitaire',
        'active',
    ];

    protected $casts = [
        'prix_unitaire' => 'decimal:2',
        'active'        => 'boolean',
    ];

    // ── Relations ──────────────────────────────────────────────
    public function plan(): BelongsTo
    {
        return $this->belongsTo(TariffPlan::class, 'tariff_plan_id', 'id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(BillableService::class, 'billable_service_id', 'id');
    }

    // ── Scopes ────────────────────────────────────────────────
    public function scopeActifs($query)
    {
        return $query->where('active', true);
    }

    public function scopePourPlan($query, int $planId)
    {
        return $query->where('tariff_plan_id', $planId)
                     ->where('active', true);
    }

    public function scopePourPlanEtService($query, int $planId, int $serviceId)
    {
        return $query->where('tariff_plan_id', $planId)
                     ->where('billable_service_id', $serviceId)
                     ->where('active', true);
    }
}