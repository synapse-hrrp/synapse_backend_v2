<?php

namespace Modules\Pharmacie\App\Services;

use Modules\Pharmacie\App\Interfaces\PricingInterface;

class PricingService implements PricingInterface
{
    /**
     * Taux de marge : PA × 1.40
     */
    private const TAUX_MARGE = 1.40;

    /**
     * TVA (18%)
     */
    private const TVA = 0.18;

    /**
     * Contribution Autonome (1%)
     */
    private const CA = 0.01;

    /**
     * Calculer prix de vente TTC
     * 
     * Formule :
     * - Si taxable : (PA × 1.40) × (1 + TVA + CA) = PA × 1.40 × 1.19
     * - Si non taxable : PA × 1.40
     */
    public function calculerPrixVenteTTC(float $prixAchat, bool $taxable): float
    {
        $prixBase = $prixAchat * self::TAUX_MARGE;

        if ($taxable) {
            // Appliquer TVA + CA
            $prixBase = $prixBase * (1 + self::TVA + self::CA);
        }

        return round($prixBase, 2);
    }

    /**
     * Calculer montant d'une ligne
     */
    public function calculerMontantLigne(float $prixUnitaireTTC, int $quantite): float
    {
        return round($prixUnitaireTTC * $quantite, 2);
    }

    /**
     * Calculer montant total d'une vente
     */
    public function calculerMontantVente(array $lignes): float
    {
        $total = 0;

        foreach ($lignes as $ligne) {
            $total += $ligne['montant_ligne_ttc'];
        }

        return round($total, 2);
    }
}