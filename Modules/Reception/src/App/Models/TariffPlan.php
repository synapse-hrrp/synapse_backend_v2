<?php

namespace Modules\Reception\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TariffPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nom',
        'description',
        'type',
        'active',

        // ✅ Règle métier : paiement obligatoire selon le plan
        'paiement_obligatoire',
    ];

    protected $casts = [
        'active' => 'boolean',
        'paiement_obligatoire' => 'boolean',
    ];

    public function tariffItems(): HasMany
    {
        return $this->hasMany(TariffItem::class, 'tariff_plan_id');
    }

    public function scopeActifs($query)
    {
        return $query->where('active', true);
    }

    // ✅ Accessors EN (si ton front/API veut rester en anglais)
    public function getNameAttribute() { return $this->nom; }
    public function getDescriptionAttribute() { return $this->attributes['description'] ?? null; }
    public function getIsActiveAttribute() { return $this->active; }

    public function getPaymentRequiredAttribute()
    {
        return $this->paiement_obligatoire;
    }
}