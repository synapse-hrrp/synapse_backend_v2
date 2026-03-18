<?php

namespace Modules\Pharmacie\App\Interfaces;

interface VenteInterface
{
    /**
     * Créer une vente avec FEFO + mouvements
     */
    public function creerVente(array $data): mixed;

    /**
     * Annuler une vente (remise en stock sur mêmes lots)
     */
    public function annulerVente(int $venteId): bool;

    /**
     * Vérifier si une vente peut être annulée
     */
    public function peutAnnuler(int $venteId): bool;
}