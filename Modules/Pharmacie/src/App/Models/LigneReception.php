<?php

namespace Modules\Pharmacie\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneReception extends Model
{
    protected $table = 'ligne_receptions';

    protected $fillable = [
        'reception_id',
        'produit_id',
        'depot_id',
        'quantite',
        'numero_lot',
        'date_peremption',
        'date_fabrication',
        'pays_origine',
        'prix_achat_unitaire_ht',
        'tva_applicable',
        'tva_pourcentage',
        'prix_achat_unitaire_ttc',
        'coefficient_marge',
        'prix_vente_unitaire',
        'montant_achat_ht',
        'montant_achat_ttc',
        'prix_vente_unitaire_ht',
        'prix_vente_unitaire_ttc',
        'marge_prevue_ht',
        'marge_prevue_ttc',
        'taux_marge_prevu',
        'montant_vente_total',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prix_achat_unitaire_ht' => 'decimal:2',
        'tva_applicable' => 'boolean',
        'tva_pourcentage' => 'decimal:2',
        'prix_achat_unitaire_ttc' => 'decimal:2',
        'coefficient_marge' => 'decimal:2',
        'prix_vente_unitaire' => 'decimal:2',
        'montant_achat_ht' => 'decimal:2',
        'montant_achat_ttc' => 'decimal:2',
        'prix_vente_unitaire_ht' => 'decimal:2',
        'prix_vente_unitaire_ttc' => 'decimal:2',
        'marge_prevue_ht' => 'decimal:2',
        'marge_prevue_ttc' => 'decimal:2',
        'taux_marge_prevu' => 'decimal:2',
        'montant_vente_total' => 'decimal:2',
        'date_peremption' => 'date',
        'date_fabrication' => 'date',
    ];

    protected $attributes = [
        'tva_applicable' => true,
        'tva_pourcentage' => 18.00,
        // SUPPRIMÉ : 'coefficient_marge' => 1.30,  // On le récupère du produit maintenant
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($ligneReception) {
            try {
                // ✅ NOUVEAU : Récupérer le coefficient depuis le produit si non fourni
                if (empty($ligneReception->coefficient_marge)) {
                    $produit = Produit::find($ligneReception->produit_id);
                    $ligneReception->coefficient_marge = $produit->coefficient_marge_defaut ?? 1.30;
                }
                
                // ✅ TVA par défaut si non fournie
                if (empty($ligneReception->tva_pourcentage)) {
                    $ligneReception->tva_pourcentage = 18.00;
                }

                // 1. Calculer prix achat TTC
                if (!empty($ligneReception->prix_achat_unitaire_ht)) {
                    $prixHT = (float) $ligneReception->prix_achat_unitaire_ht;
                    $tvaPourcentage = (float) $ligneReception->tva_pourcentage;
                    
                    $ligneReception->prix_achat_unitaire_ttc = $prixHT * (1 + $tvaPourcentage / 100);
                }

                // 2. Calculer prix de vente et marges
                if (!empty($ligneReception->prix_achat_unitaire_ttc)) {
                    $prixAchatTTC = (float) $ligneReception->prix_achat_unitaire_ttc;
                    $prixAchatHT = (float) ($ligneReception->prix_achat_unitaire_ht ?? 0);
                    $coefficient = (float) $ligneReception->coefficient_marge;
                    $tvaPourcentage = (float) $ligneReception->tva_pourcentage;

                    // Prix vente TTC
                    if (empty($ligneReception->prix_vente_unitaire_ttc)) {
                        $ligneReception->prix_vente_unitaire_ttc = $prixAchatTTC * $coefficient;
                        $ligneReception->prix_vente_unitaire = $ligneReception->prix_vente_unitaire_ttc;
                    }

                    // Prix vente HT
                    $ligneReception->prix_vente_unitaire_ht = $ligneReception->prix_vente_unitaire_ttc / (1 + $tvaPourcentage / 100);

                    // Marges
                    $ligneReception->marge_prevue_ht = $ligneReception->prix_vente_unitaire_ht - $prixAchatHT;
                    $ligneReception->marge_prevue_ttc = $ligneReception->prix_vente_unitaire_ttc - $prixAchatTTC;

                    // Taux marge
                    if ($prixAchatHT > 0) {
                        $ligneReception->taux_marge_prevu = ($ligneReception->marge_prevue_ht / $prixAchatHT) * 100;
                    }
                }

                // 3. Calculer les montants totaux
                $quantite = (int) ($ligneReception->quantite ?? 0);
                
                if (!empty($ligneReception->prix_achat_unitaire_ht)) {
                    $ligneReception->montant_achat_ht = $ligneReception->prix_achat_unitaire_ht * $quantite;
                }
                
                if (!empty($ligneReception->prix_achat_unitaire_ttc)) {
                    $ligneReception->montant_achat_ttc = $ligneReception->prix_achat_unitaire_ttc * $quantite;
                }
                
                if (!empty($ligneReception->prix_vente_unitaire_ttc)) {
                    $ligneReception->montant_vente_total = $ligneReception->prix_vente_unitaire_ttc * $quantite;
                }

            } catch (\Exception $e) {
                \Log::error('Erreur calcul ligne réception: ' . $e->getMessage());
            }
        });
    }

    // Relations
    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }
}