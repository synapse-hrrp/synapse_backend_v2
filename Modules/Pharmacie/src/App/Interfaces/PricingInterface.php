<?php

namespace Modules\Pharmacie\App\Interfaces;

interface PricingInterface
{
    /**
     * Calculer le prix de vente TTC (PA × 1.40 avec taxes)
     */
    public function calculerPrixVenteTTC(float $prixAchat, bool $taxable): float;

    /**
     * Calculer le montant total d'une ligne de vente
     */
    public function calculerMontantLigne(float $prixUnitaireTTC, int $quantite): float;

    /**
     * Calculer le montant total d'une vente
     */
    public function calculerMontantVente(array $lignes): float;
}