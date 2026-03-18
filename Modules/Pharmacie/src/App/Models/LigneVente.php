<?php

namespace Modules\Pharmacie\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class LigneVente extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;  // ✅ CORRIGÉ

    protected $auditDriver = 'database';
    protected $auditModel = PharmacieAudit::class;

    protected $fillable = [
        'vente_id',
        'produit_id',
        'quantite',
        'prix_unitaire_ttc',
        'montant_ligne_ttc',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prix_unitaire_ttc' => 'decimal:2',
        'montant_ligne_ttc' => 'decimal:2',
    ];

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(LigneVenteStock::class);
    }
}