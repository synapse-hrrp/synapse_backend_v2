<?php

namespace Modules\Pharmacie\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use OwenIt\Auditing\Contracts\Auditable;

class Stock extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $auditDriver = 'database';
    protected $auditModel = PharmacieAudit::class;

    protected $fillable = [
        'produit_id',
        'depot_id',
        'numero_lot',
        'date_peremption',
        'quantite',
        'prix_achat',                    // Ancienne colonne (compatibilité)
        'prix_achat_unitaire_ht',
        'prix_achat_unitaire_ttc',
        'taux_tva',
        'montant_tva_unitaire',
        // ← NOUVEAUX CHAMPS PRIX DE VENTE
        'prix_vente_unitaire_ht',
        'prix_vente_unitaire_ttc',
        'marge_unitaire_ht',
        'marge_unitaire_ttc',
        'taux_marge',
    ];

    protected $casts = [
        'date_peremption'         => 'date',
        'quantite'                => 'integer',
        'prix_achat'              => 'decimal:2',
        'prix_achat_unitaire_ht'  => 'decimal:2',
        'prix_achat_unitaire_ttc' => 'decimal:2',
        'taux_tva'                => 'decimal:2',
        'montant_tva_unitaire'    => 'decimal:2',
        // ← NOUVEAUX CASTS
        'prix_vente_unitaire_ht'  => 'decimal:2',
        'prix_vente_unitaire_ttc' => 'decimal:2',
        'marge_unitaire_ht'       => 'decimal:2',
        'marge_unitaire_ttc'      => 'decimal:2',
        'taux_marge'              => 'decimal:2',
    ];

    /**
     * Boot method pour calculer automatiquement la TVA et prix de vente
     */
    protected static function boot()
    {
        parent::boot();

        // Avant la création
        static::creating(function ($stock) {
            // Définir taux_tva par défaut si absent
            if (!$stock->taux_tva) {
                $stock->taux_tva = 18.9;
            }
            
            // Si prix_achat_unitaire_ht est fourni, calculer la TVA
            if ($stock->prix_achat_unitaire_ht) {
                $stock->calculerTVA();
            }
            
            // ← NOUVEAU : Calculer prix de vente et marges
            if ($stock->prix_achat_unitaire_ht) {
                $stock->calculerPrixVenteEtMarges();
            }
            
            // ⚠️ IMPORTANT : Assurer que prix_achat a une valeur pour MySQL
            if (!$stock->prix_achat) {
                $stock->prix_achat = $stock->prix_achat_unitaire_ht ?? 0;
            }
        });

        // Avant la mise à jour
        static::updating(function ($stock) {
            // Recalculer si HT ou TVA change
            if ($stock->isDirty('prix_achat_unitaire_ht') || $stock->isDirty('taux_tva')) {
                $stock->calculerTVA();
            }
            
            // ← NOUVEAU : Recalculer prix vente et marges si nécessaire
            if ($stock->isDirty('prix_achat_unitaire_ht') || 
                $stock->isDirty('prix_vente_unitaire_ttc')) {
                $stock->calculerPrixVenteEtMarges();
            }
            
            // Synchroniser prix_achat avec prix_achat_unitaire_ht
            if ($stock->isDirty('prix_achat_unitaire_ht') && $stock->prix_achat_unitaire_ht) {
                $stock->prix_achat = $stock->prix_achat_unitaire_ht;
            }
        });
    }

    /**
     * Calculer automatiquement la TVA
     */
    public function calculerTVA()
    {
        if ($this->prix_achat_unitaire_ht) {
            $tauxTva = $this->taux_tva ?? 18.9;
            
            // Calcul TVA unitaire
            $this->montant_tva_unitaire = round($this->prix_achat_unitaire_ht * ($tauxTva / 100), 2);
            
            // Calcul prix TTC
            $this->prix_achat_unitaire_ttc = round($this->prix_achat_unitaire_ht + $this->montant_tva_unitaire, 2);
            
            // Mettre à jour aussi l'ancienne colonne pour compatibilité
            $this->prix_achat = $this->prix_achat_unitaire_ht;
        }
    }

    /**
     * ← NOUVELLE MÉTHODE : Calculer prix de vente et marges
     */
    public function calculerPrixVenteEtMarges()
    {
        if (!$this->prix_achat_unitaire_ttc) {
            return;
        }

        // Si prix de vente pas défini, calculer avec coefficient produit
        if (!$this->prix_vente_unitaire_ttc) {
            $produit = $this->produit ?? Produit::find($this->produit_id);

            if ($produit) {
                $coefficient = $produit->coefficient_marge_defaut ?? 1.40;

                // Prix vente TTC = Prix achat TTC × Coefficient
                $this->prix_vente_unitaire_ttc = round($this->prix_achat_unitaire_ttc * $coefficient, 2);

                // Prix vente HT
                $tauxTva = $this->taux_tva ?? 18.9;
                $this->prix_vente_unitaire_ht = round($this->prix_vente_unitaire_ttc / (1 + $tauxTva / 100), 2);
            }
        }

        // Calculer les marges
        if ($this->prix_vente_unitaire_ht && $this->prix_achat_unitaire_ht) {
            // Marge HT
            $this->marge_unitaire_ht = round($this->prix_vente_unitaire_ht - $this->prix_achat_unitaire_ht, 2);

            // Marge TTC
            if ($this->prix_vente_unitaire_ttc && $this->prix_achat_unitaire_ttc) {
                $this->marge_unitaire_ttc = round($this->prix_vente_unitaire_ttc - $this->prix_achat_unitaire_ttc, 2);
            }

            // Taux de marge
            if ($this->prix_achat_unitaire_ht > 0) {
                $this->taux_marge = round(($this->marge_unitaire_ht / $this->prix_achat_unitaire_ht) * 100, 2);
            }
        }
    }

    /**
     * Accesseur : Valeur totale HT du stock
     */
    public function getValeurTotaleHtAttribute()
    {
        return ($this->prix_achat_unitaire_ht ?? 0) * $this->quantite;
    }

    /**
     * Accesseur : Valeur totale TTC du stock
     */
    public function getValeurTotaleTtcAttribute()
    {
        return ($this->prix_achat_unitaire_ttc ?? 0) * $this->quantite;
    }

    /**
     * ← NOUVEAUX ACCESSEURS : Valeur vente totale
     */
    public function getValeurVenteTotaleHtAttribute()
    {
        return ($this->prix_vente_unitaire_ht ?? 0) * $this->quantite;
    }

    public function getValeurVenteTotaleTtcAttribute()
    {
        return ($this->prix_vente_unitaire_ttc ?? 0) * $this->quantite;
    }

    public function getMargeTotaleHtAttribute()
    {
        return ($this->marge_unitaire_ht ?? 0) * $this->quantite;
    }

    public function getMargeTotaleTtcAttribute()
    {
        return ($this->marge_unitaire_ttc ?? 0) * $this->quantite;
    }

    /**
     * Accesseur : Jours avant péremption
     */
    public function getJoursAvantPeremptionAttribute()
    {
        return now()->diffInDays($this->date_peremption, false);
    }

    /**
     * Accesseur : Statut péremption
     */
    public function getStatutPeremptionAttribute()
    {
        $jours = $this->jours_avant_peremption;
        
        if ($jours < 0) {
            return 'PERIME';
        } elseif ($jours <= 30) {
            return 'PROCHE';
        } else {
            return 'BON';
        }
    }

    /**
     * Relations
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(MouvementStock::class);
    }

    public function ligneVenteStocks(): HasMany
    {
        return $this->hasMany(LigneVenteStock::class);
    }

    /**
     * Scopes
     */
    public function scopeFefo(Builder $query): Builder
    {
        return $query->where('quantite', '>', 0)
                     ->orderBy('date_peremption', 'asc');
    }

    public function scopeNonPerime(Builder $query): Builder
    {
        return $query->where('date_peremption', '>=', now()->toDateString());
    }

    public function scopePerime(Builder $query): Builder
    {
        return $query->where('date_peremption', '<', now()->toDateString());
    }

    public function scopeProche(Builder $query, int $jours = 30): Builder
    {
        return $query->whereBetween('date_peremption', [
            now()->toDateString(),
            now()->addDays($jours)->toDateString(),
        ]);
    }

    public function scopeBon(Builder $query, int $jours = 30): Builder
    {
        return $query->where('date_peremption', '>', now()->addDays($jours)->toDateString());
    }

    public function scopeDisponibles(Builder $query): Builder
    {
        return $query->where('quantite', '>', 0);
    }

    public function scopeProchesPeremption(Builder $query, int $jours = 30): Builder
    {
        return $query->proche($jours);
    }
}