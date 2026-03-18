<?php

namespace Modules\Pharmacie\App\Interfaces;

interface StockInterface
{
    /**
     * Récupérer les stocks FEFO pour un produit et dépôt
     */
    public function getStockFefo(int $produitId, int $depotId);

    /**
     * Vérifier la disponibilité d'un stock (FEFO + non périmé)
     */
    public function verifierDisponibilite(int $produitId, int $depotId, int $quantite): bool;

    /**
     * Prélever du stock (FEFO)
     */
    public function preleverStock(int $produitId, int $depotId, int $quantite): array;

    /**
     * Ajouter/Mettre à jour le stock (upsert)
     */
    public function upsertStock(array $data): void;

    /**
     * Récupérer les stocks périmés
     */
    public function getStocksPerimes();

    /**
     * Récupérer les stocks proches de la péremption
     */
    public function getStocksProches(int $jours = 30);

    /**
     * Récupérer les stocks en bon état
     */
    public function getStocksBon(int $jours = 30);

    /**
     * Vérifier seuils min/max
     */
    public function verifierSeuils();
}