<?php

namespace Modules\Reception\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ✅ demandeur = User connecté (users.id)
use App\Models\User;

class BillingRequest extends Model
{
    protected $table = 't_billing_requests';

    protected $fillable = [
        'id_patient',
        'module_source',
        'ref_source',
        'id_agent_demandeur', // ✅ stocke users.id
        'statut',
        'montant_total',
        'montant_paye',
    ];

    protected $casts = [
        'id_patient' => 'integer',
        'ref_source' => 'string',          // ⚠️ peut contenir "register:12" ou "12" => string
        'id_agent_demandeur' => 'integer',
        'montant_total' => 'decimal:2',
        'montant_paye'  => 'decimal:2',
    ];

    // ✅ Statuts BR (alignés sur finance)
    public const STATUS_PENDING = 'pending';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    public static function allowedStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PARTIALLY_PAID,
            self::STATUS_PAID,
            self::STATUS_CANCELLED,
        ];
    }

    // ✅ Accessors EN (front)
    public function getPatientIdAttribute() { return $this->attributes['id_patient'] ?? null; }
    public function getSourceModuleAttribute() { return $this->attributes['module_source'] ?? null; }
    public function getSourceRefAttribute() { return $this->attributes['ref_source'] ?? null; }
    public function getRequestedByAgentIdAttribute() { return $this->attributes['id_agent_demandeur'] ?? null; }
    public function getStatusAttribute() { return $this->attributes['statut'] ?? null; }
    public function getTotalAmountAttribute() { return $this->attributes['montant_total'] ?? 0; }
    public function getPaidAmountAttribute() { return $this->attributes['montant_paye'] ?? 0; }

    // ✅ Helpers
    public function remainingAmount(): float
    {
        $total = (float) ($this->montant_total ?? 0);
        $paid  = (float) ($this->montant_paye ?? 0);
        return max(0, round($total - $paid, 2));
    }

    public function isPaid(): bool
    {
        return $this->remainingAmount() <= 0.0001;
    }

    public function refreshTotals(): void
    {
        $total = (float) $this->items()->sum('total_ligne');
        $this->montant_total = round($total, 2);
        $this->save();
    }

    // ✅ Relations
    public function items(): HasMany
    {
        return $this->hasMany(BillingRequestItem::class, 'id_demande_facturation', 'id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Patient::class, 'id_patient', 'id');
    }

    /**
     * ✅ Demandeur = User (users.id)
     * IMPORTANT : tu stockes users.id dans id_agent_demandeur
     */
    public function requestedByAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_agent_demandeur', 'id');
    }

    /**
     * ✅ Lien registre
     * Chez toi ref_source = (string) $entry->id, et module_source='reception'
     */
    public function registerEntry(): BelongsTo
    {
        return $this->belongsTo(DailyRegisterEntry::class, 'ref_source', 'id')
            ->where('module_source', 'reception');
    }
}