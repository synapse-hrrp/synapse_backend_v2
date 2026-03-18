<?php

namespace Modules\Pharmacie\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class LigneCommande extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $auditDriver = 'database';
    protected $auditModel = PharmacieAudit::class;

    protected $fillable = [
        'commande_id',
        'produit_id',
        'quantite_commandee',
        'quantite_recue',
        'prix_unitaire',
        'stock_actuel',
        'cmh',
        'seuil_max',
        'seuil_min',
        'raison_commande',
    ];

    protected $casts = [
        'quantite_commandee' => 'integer',
        'quantite_recue' => 'integer',
        'prix_unitaire' => 'decimal:2',
        'stock_actuel' => 'integer',
        'cmh' => 'decimal:2',
        'seuil_max' => 'integer',
        'seuil_min' => 'integer',
    ];

    /**
     * Commande
     */
    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    /**
     * Produit
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    /**
     * Calculer montant ligne
     */
    public function getMontantLigneAttribute(): float
    {
        return $this->quantite_commandee * $this->prix_unitaire;
    }

    /**
     * Vérifier si la ligne est complètement reçue
     */
    public function estCompleteRecue(): bool
    {
        return $this->quantite_recue >= $this->quantite_commandee;
    }

    /**
     * Calculer quantité restante à recevoir
     */
    public function getQuantiteRestanteAttribute(): int
    {
        return max(0, $this->quantite_commandee - $this->quantite_recue);
    }

    /**
     * Vérifier si ligne générée automatiquement
     */
    public function estAutomatique(): bool
    {
        return $this->raison_commande !== null;
    }
}