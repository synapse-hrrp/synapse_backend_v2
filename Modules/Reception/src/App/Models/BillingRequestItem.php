<?php

namespace Modules\Reception\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingRequestItem extends Model
{
    protected $table = 't_billing_request_items';

    protected $fillable = [
        'id_demande_facturation',
        'billable_service_id',
        'tariff_item_id',
        'quantite',
        'prix_unitaire',
        'total_ligne',
        'notes', // ✅ adapte si ta colonne s'appelle autrement (cf. DESCRIBE)
    ];

    protected $casts = [
        'id_demande_facturation' => 'integer',
        'billable_service_id'    => 'integer',
        'tariff_item_id'         => 'integer',
        'quantite'               => 'integer',
        'prix_unitaire'          => 'decimal:2',
        'total_ligne'            => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            $qty = (int) ($item->quantite ?? 1);
            if ($qty <= 0) $qty = 1;

            $unit = (float) ($item->prix_unitaire ?? 0);
            if ($unit < 0) $unit = 0;

            $item->quantite = $qty;
            $item->prix_unitaire = $unit;
            $item->total_ligne = round($qty * $unit, 2);
        });
    }

    // -------------------------------------------------
    // Relations (✅ indispensables pour items.billableService)
    // -------------------------------------------------

    public function billingRequest(): BelongsTo
    {
        return $this->belongsTo(BillingRequest::class, 'id_demande_facturation', 'id');
    }

    public function billableService(): BelongsTo
    {
        return $this->belongsTo(BillableService::class, 'billable_service_id', 'id');
    }

    public function tariffItem(): BelongsTo
    {
        return $this->belongsTo(TariffItem::class, 'tariff_item_id', 'id');
    }

    // -------------------------------------------------
    // Accessors API (compat front)
    // -------------------------------------------------

    public function getBillingRequestIdAttribute() { return $this->attributes['id_demande_facturation'] ?? null; }
    public function getServiceIdAttribute() { return $this->attributes['billable_service_id'] ?? null; }
    public function getQtyAttribute() { return (int) ($this->attributes['quantite'] ?? 0); }
    public function getUnitPriceAttribute() { return $this->attributes['prix_unitaire'] ?? null; }
    public function getLineTotalAttribute() { return (float) ($this->attributes['total_ligne'] ?? 0); }

    // ⚠️ si ta colonne DB n'est pas "notes", adapte ici
    public function getNotesAttribute() { return $this->attributes['notes'] ?? null; }
}