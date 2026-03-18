<?php

namespace Modules\Pharmacie\App\Services;

use Modules\Pharmacie\App\Interfaces\StockInterface;
use Modules\Pharmacie\App\Repository\StockRepository;
use Exception;

class StockService implements StockInterface
{
    public function __construct(
        private StockRepository $stockRepository
    ) {}

    /**
     * Récupérer stocks FEFO
     */
    public function getStockFefo(int $produitId, int $depotId)
    {
        return $this->stockRepository->getStockFefo($produitId, $depotId);
    }

    /**
     * Vérifier disponibilité (total non périmé)
     */
    public function verifierDisponibilite(int $produitId, int $depotId, int $quantite): bool
    {
        $totalDispo = $this->stockRepository->getTotalDisponible($produitId, $depotId);
        return $totalDispo >= $quantite;
    }

    /**
     * Prélever stock avec FEFO + blocage périmé
     * 
     * Retourne : [
     *   ['stock_id' => X, 'quantite' => Y],
     *   ...
     * ]
     */
    public function preleverStock(int $produitId, int $depotId, int $quantite): array
    {
        // 1. Récupérer stocks FEFO (non périmés, triés par date)
        $stocks = $this->getStockFefo($produitId, $depotId);

        if ($stocks->isEmpty()) {
            throw new Exception("Aucun stock disponible pour ce produit dans ce dépôt.");
        }

        // 2. Vérifier disponibilité totale
        if (!$this->verifierDisponibilite($produitId, $depotId, $quantite)) {
            throw new Exception("Stock insuffisant. Disponible : " . $this->stockRepository->getTotalDisponible($produitId, $depotId));
        }

        // 3. Prélever lot par lot (FEFO)
        $prelevements = [];
        $quantiteRestante = $quantite;

        foreach ($stocks as $stock) {
            if ($quantiteRestante <= 0) {
                break;
            }

            // Bloquer lots périmés (sécurité supplémentaire)
            if ($stock->date_peremption < now()->toDateString()) {
                continue;
            }

            $quantiteAPrever = min($stock->quantite, $quantiteRestante);

            $prelevements[] = [
                'stock_id' => $stock->id,
                'quantite' => $quantiteAPrever,
            ];

            $quantiteRestante -= $quantiteAPrever;
        }

        if ($quantiteRestante > 0) {
            throw new Exception("Impossible de prélever la quantité demandée avec FEFO.");
        }

        return $prelevements;
    }

    /**
     * Upsert stock (réception)
     */
    public function upsertStock(array $data): void
    {
        $this->stockRepository->upsertStock($data);
    }

    /**
     * Stocks périmés
     */
    public function getStocksPerimes()
    {
        return $this->stockRepository->getStocksPerimes();
    }

    /**
     * Stocks proches péremption (30 jours)
     */
    public function getStocksProches(int $jours = 30)
    {
        return $this->stockRepository->getStocksProches($jours);
    }

    /**
     * Stocks en bon état (> 30 jours)
     */
    public function getStocksBon(int $jours = 30)
    {
        return $this->stockRepository->getStocksBon($jours);
    }

    /**
     * Vérifier seuils min/max
     */
    public function verifierSeuils()
    {
        return $this->stockRepository->verifierSeuils();
    }
}