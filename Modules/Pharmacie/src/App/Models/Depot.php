<?php

namespace Modules\Pharmacie\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Depot extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $auditDriver = 'database';
    protected $auditModel = PharmacieAudit::class;

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    /**
     * Stocks
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Ventes
     */
    public function ventes(): HasMany
    {
        return $this->hasMany(Vente::class);
    }

    /**
     * Seuils stocks
     */
    public function seuilStocks(): HasMany
    {
        return $this->hasMany(SeuilStock::class);
    }

    /**
     * Lignes réceptions
     */
    public function ligneReceptions(): HasMany
    {
        return $this->hasMany(LigneReception::class);
    }

    /**
     * Consommations produits
     */
    public function consommations(): HasMany
    {
        return $this->hasMany(ConsommationProduit::class);
    }

    /**
     * Scope pour dépôts actifs
     */
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    /**
     * Calculer stock total du dépôt
     */
    public function stockTotal(): int
    {
        return $this->stocks()
            ->where('quantite', '>', 0)
            ->where('date_peremption', '>=', now())
            ->sum('quantite');
    }
}