<?php

namespace Modules\Pharmacie\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Fournisseur extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $auditDriver = 'database';
    protected $auditModel = PharmacieAudit::class;

    protected $fillable = [
        'nom',
        'telephone',
        'email',
        'adresse',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    /**
     * Commandes
     */
    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class);
    }

    /**
     * Réceptions
     */
    public function receptions(): HasMany
    {
        return $this->hasMany(Reception::class);
    }

    /**
     * Scope pour fournisseurs actifs
     */
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }
}