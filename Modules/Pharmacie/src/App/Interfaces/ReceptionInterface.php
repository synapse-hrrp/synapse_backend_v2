<?php

namespace Modules\Pharmacie\App\Interfaces;

interface ReceptionInterface
{
    /**
     * Créer une réception + entrée stock
     */
    public function creerReception(array $data): mixed;

    /**
     * Mettre à jour le statut de la commande liée
     */
    public function mettreAJourCommande(int $commandeId): void;
}