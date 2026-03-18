<?php

namespace Modules\Reactifs\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reactif extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'nom', 'unite', 'stock_actuel', 'stock_minimum',
        'stock_maximum', 'localisation', 'date_peremption', 'actif', 'notes',
    ];

    protected $casts = [
        'stock_actuel'    => 'decimal:3',
        'stock_minimum'   => 'decimal:3',
        'stock_maximum'   => 'decimal:3',
        'date_peremption' => 'date',
        'actif'           => 'boolean',
    ];

    // ── Relations ──────────────────────────────────────────
    public function mouvements()
    {
        return $this->hasMany(ReactifStockMouvement::class);
    }

    public function consommations()
    {
        return $this->hasMany(ReactifConsommation::class);
    }

    public function examenTypes()
    {
        return $this->hasMany(ReactifExamenType::class);
    }

    public function commandeLignes()
    {
        return $this->hasMany(ReactifCommandeLigne::class);
    }

    // ── Accesseurs ─────────────────────────────────────────
    public function getEstEnAlertAttribute(): bool
    {
        return $this->stock_actuel <= $this->stock_minimum;
    }

    public function getEstEnRuptureAttribute(): bool
    {
        return $this->stock_actuel <= 0;
    }
}