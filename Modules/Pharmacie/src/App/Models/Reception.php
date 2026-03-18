<?php

namespace Modules\Pharmacie\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Reception extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $auditDriver = 'database';
    protected $auditModel = PharmacieAudit::class;

    protected $fillable = [
        'numero',
        'commande_id',
        'fournisseur_id',
        'depot_id',
        'bon_livraison',
        'facture_fournisseur',
        'date_reception',
        'date_livraison_prevue',
        'date_livraison_reelle',
        'statut',
        'validee_par_user_id',
        'validee_at',
        'observations',
        'montant_total_ht',
        'montant_total_ttc',
        'montant_total_vente',
        'marge_totale_prevue',
        'fichier_export_path',
        'format_export',
        'exporte_at',
        'exporte_par_user_id',
    ];

    protected $casts = [
        'date_reception' => 'date',
        'date_livraison_prevue' => 'date',
        'date_livraison_reelle' => 'date',
        'validee_at' => 'datetime',
        'montant_total_ht' => 'decimal:2',
        'montant_total_ttc' => 'decimal:2',
        'montant_total_vente' => 'decimal:2',
        'marge_totale_prevue' => 'decimal:2',
        'exporte_at' => 'datetime',
    ];

    /**
     * Commande associée
     */
    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    /**
     * Fournisseur
     */
    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    /**
     * Dépôt
     */
    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    /**
     * Utilisateur ayant validé
     */
    public function validateurUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'validee_par_user_id');
    }

    /**
     * Utilisateur ayant exporté
     */
    public function exporteurUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'exporte_par_user_id');
    }

    /**
     * Lignes de réception
     */
    public function lignes(): HasMany
    {
        return $this->hasMany(LigneReception::class);
    }

    /**
     * Mouvements de stock
     */
    public function mouvements(): HasMany
    {
        return $this->hasMany(MouvementStock::class);
    }

    /**
     * ✅ CORRIGÉ : Calculer TOUS les montants totaux depuis les lignes
     */
    public function calculerMontants(): void
    {
        $this->montant_total_ht = $this->lignes()->sum('montant_achat_ht');
        $this->montant_total_ttc = $this->lignes()->sum('montant_achat_ttc');
        $this->montant_total_vente = $this->lignes()->sum('montant_vente_total');
        
        // ✅ CORRECTION : Calculer la marge totale (marge_unitaire × quantité)
        $this->marge_totale_prevue = $this->lignes()
            ->get()
            ->sum(function ($ligne) {
                return $ligne->marge_prevue_ttc * $ligne->quantite;
            });
        
        $this->save();
    }

    /**
     * ✅ Accesseur pour le taux de marge global
     */
    public function getTauxMargePrevuAttribute(): ?float
    {
        if (!$this->montant_total_ht || $this->montant_total_ht == 0) {
            return null;
        }
        
        // Convertir marge TTC en HT (approximatif)
        $margeHt = $this->marge_totale_prevue 
            ? $this->marge_totale_prevue / 1.18
            : 0;
        
        return round(($margeHt / $this->montant_total_ht) * 100, 2);
    }

    /**
     * Vérifier si la réception est validée
     */
    public function estValidee(): bool
    {
        return $this->validee_at !== null;
    }

    /**
     * Vérifier si la réception est exportée
     */
    public function estExportee(): bool
    {
        return $this->exporte_at !== null;
    }

    /**
     * Scope pour réceptions validées
     */
    public function scopeValidees($query)
    {
        return $query->whereNotNull('validee_at');
    }

    /**
     * Scope pour réceptions non validées
     */
    public function scopeNonValidees($query)
    {
        return $query->whereNull('validee_at');
    }
}