<?php

namespace Modules\Reception\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyRegisterEntryItem extends Model
{
    protected $table = 'reception_registre_journalier_lignes';

    protected $fillable = [
        'id_entree_journal',
        'billable_service_id',
        'tariff_item_id',
        'quantite',
        'prix_unitaire',
        'total_ligne',
        'remarques',
    ];

    protected $casts = [
        'id_entree_journal' => 'integer',
        'billable_service_id' => 'integer',
        'tariff_item_id' => 'integer',
        'quantite' => 'integer',
        'prix_unitaire' => 'decimal:2',
        'total_ligne' => 'decimal:2',
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

    // ✅ Relations
    public function entry(): BelongsTo
    {
        return $this->belongsTo(DailyRegisterEntry::class, 'id_entree_journal', 'id');
    }

    public function tariffItem(): BelongsTo
    {
        return $this->belongsTo(TariffItem::class, 'tariff_item_id', 'id');
    }

    public function billableService(): BelongsTo
    {
        return $this->belongsTo(BillableService::class, 'billable_service_id', 'id');
    }

    // ✅ Accessors EN (compat front)
    public function getDailyRegisterEntryIdAttribute() { return (int) ($this->attributes['id_entree_journal'] ?? 0); }
    public function getServiceIdAttribute() { return (int) ($this->attributes['billable_service_id'] ?? 0); }
    public function getTariffItemIdAttribute() { return (int) ($this->attributes['tariff_item_id'] ?? 0); }

    public function getQtyAttribute() { return (int) ($this->attributes['quantite'] ?? 0); }
    public function getUnitPriceAttribute() { return $this->attributes['prix_unitaire'] ?? null; }
    public function getLineTotalAttribute() { return (float) ($this->attributes['total_ligne'] ?? 0); }
    public function getNotesAttribute() { return $this->attributes['remarques'] ?? null; }
}