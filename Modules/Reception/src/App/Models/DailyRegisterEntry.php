<?php

namespace Modules\Reception\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// ✅ Créateur = users.id
use App\Models\User;

// Finance
use Modules\Finance\App\Models\FactureOfficielle;
use Modules\Finance\App\Models\FinancePayment;

// Imports
use Modules\Reception\App\Models\TariffPlan;
use Modules\Reception\App\Models\BillableService;
use Modules\Reception\App\Models\BillingRequest;
use Modules\Reception\App\Models\DailyRegisterEntryItem;

class DailyRegisterEntry extends Model
{
    protected $table = 'reception_registre_journalier';

    protected $fillable = [
        'id_patient',
        'id_agent_createur',
        'date_arrivee',
        'motif',
        'urgence',
        'statut',
        'id_demande_paiement',
        'tariff_plan_id',
    ];

    protected $casts = [
        'date_arrivee' => 'datetime',
        'urgence'      => 'boolean',
    ];

    // ✅ Statuts registre
    public const STATUS_OPEN            = 'open';
    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';
    public const STATUS_CLOSED          = 'closed';
    public const STATUS_CANCELLED       = 'cancelled';

    public static function allowedStatuses(): array
    {
        return [
            self::STATUS_OPEN,
            self::STATUS_AWAITING_PAYMENT,
            self::STATUS_CLOSED,
            self::STATUS_CANCELLED,
        ];
    }

    public function isLocked(): bool
    {
        // ✅ fermé OU annulé = verrouillé
        return $this->statut === self::STATUS_CANCELLED || $this->statut === self::STATUS_CLOSED;
    }

    // Paiement obligatoire ?
    public function paymentRequired(): bool
    {
        $planId = $this->getAttribute('tariff_plan_id');
        if (!$planId) return true;

        if ($this->relationLoaded('tariffPlan') && $this->tariffPlan) {
            return (bool) $this->tariffPlan->paiement_obligatoire;
        }

        return (bool) TariffPlan::query()
            ->whereKey($planId)
            ->value('paiement_obligatoire');
    }

    public function requiresAppointmentFor(BillableService $service): bool
    {
        return (bool) $service->rendez_vous_obligatoire && !$this->urgence;
    }

    public function canAddService(BillableService $service): array
    {
        if ($this->isLocked()) {
            return [false, "Entrée verrouillée (fermée ou annulée)."];
        }
        if ($this->requiresAppointmentFor($service)) {
            return [false, "Rendez-vous requis pour cette prestation (sauf urgence)."];
        }
        return [true, null];
    }

    /**
     * ✅ Sync automatique statuts:
     * - BillingRequest: pending / partially_paid / paid
     * - FactureOfficielle: unpaid / partially_paid / paid (si existe)
     * - DailyRegisterEntry:
     *    - open si paiement non obligatoire
     *    - awaiting_payment si non payé ou partiel
     *    - closed si payé totalement
     */
    public function syncBillingAndStatus(): void
    {
        if ($this->statut === self::STATUS_CANCELLED) {
            // Annulé = ne touche plus
            return;
        }

        // Si paiement non obligatoire => open (et pas closed auto)
        if (!$this->paymentRequired()) {
            if ($this->statut !== self::STATUS_OPEN) {
                $this->statut = self::STATUS_OPEN;
                $this->save();
            }
            return;
        }

        $billingRequestId = (int) ($this->id_demande_paiement ?? 0);
        if ($billingRequestId <= 0) {
            if ($this->statut !== self::STATUS_AWAITING_PAYMENT) {
                $this->statut = self::STATUS_AWAITING_PAYMENT;
                $this->save();
            }
            return;
        }

        $br = BillingRequest::query()->find($billingRequestId);
        if (!$br) {
            if ($this->statut !== self::STATUS_AWAITING_PAYMENT) {
                $this->statut = self::STATUS_AWAITING_PAYMENT;
                $this->save();
            }
            return;
        }

        $totalDu   = (float) ($br->montant_total ?? 0);
        $totalPaye = FinancePayment::totalPaye('t_billing_requests', (int) $br->id);

        $isPaid    = ($totalDu > 0) && (($totalPaye + 0.0001) >= $totalDu);
        $isPartial = ($totalPaye > 0) && !$isPaid;

        // ✅ BillingRequest statut
        $brStatus = $isPaid
            ? (defined(BillingRequest::class.'::STATUS_PAID') ? BillingRequest::STATUS_PAID : 'paid')
            : ($isPartial
                ? (defined(BillingRequest::class.'::STATUS_PARTIALLY_PAID') ? BillingRequest::STATUS_PARTIALLY_PAID : 'partially_paid')
                : (defined(BillingRequest::class.'::STATUS_PENDING') ? BillingRequest::STATUS_PENDING : 'pending')
            );

        $br->update([
            'montant_paye' => round($totalPaye, 2),
            'statut'       => $brStatus,
        ]);

        // ✅ Facture officielle statut (si existe)
        $facture = FactureOfficielle::query()
            ->where('module_source', 'reception')
            ->where('table_source', 't_billing_requests')
            ->where('source_id', (int) $br->id)
            ->first();

        if ($facture) {
            $facture->statut_paiement = $isPaid
                ? (defined(FactureOfficielle::class.'::PAY_PAID') ? FactureOfficielle::PAY_PAID : 'paid')
                : ($isPartial
                    ? (defined(FactureOfficielle::class.'::PAY_PARTIALLY') ? FactureOfficielle::PAY_PARTIALLY : 'partially_paid')
                    : (defined(FactureOfficielle::class.'::PAY_UNPAID') ? FactureOfficielle::PAY_UNPAID : 'unpaid')
                );
            $facture->save();
        }

        // ✅ Registre : closed automatique si payé totalement
        $newEntryStatus = $isPaid ? self::STATUS_CLOSED : self::STATUS_AWAITING_PAYMENT;

        if ($this->statut !== $newEntryStatus) {
            $this->statut = $newEntryStatus;
            $this->save();
        }
    }

    // Accessors EN
    public function getPatientIdAttribute() { return $this->attributes['id_patient'] ?? null; }
    public function getCreatedByAgentIdAttribute() { return $this->attributes['id_agent_createur'] ?? null; }
    public function getArrivalAtAttribute() { return $this->attributes['date_arrivee'] ?? null; }
    public function getReasonAttribute() { return $this->attributes['motif'] ?? null; }
    public function getIsEmergencyAttribute() { return (bool)($this->attributes['urgence'] ?? false); }
    public function getStatusAttribute() { return $this->attributes['statut'] ?? null; }
    public function getBillingRequestIdAttribute() { return $this->attributes['id_demande_paiement'] ?? null; }
    public function getTariffPlanIdAttribute() { return $this->attributes['tariff_plan_id'] ?? null; }

    // Relations
    public function createdByAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_agent_createur', 'id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Patient::class, 'id_patient', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DailyRegisterEntryItem::class, 'id_entree_journal', 'id');
    }

    public function billingRequest(): BelongsTo
    {
        return $this->belongsTo(BillingRequest::class, 'id_demande_paiement', 'id');
    }

    public function tariffPlan(): BelongsTo
    {
        return $this->belongsTo(TariffPlan::class, 'tariff_plan_id', 'id');
    }
}