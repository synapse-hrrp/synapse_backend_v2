<?php

namespace Modules\Pharmacie\App\Repository;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Pharmacie\App\Models\Stock;

class StockRepository
{
    public function getStockFefo(int $produitId, int $depotId)
    {
        return Stock::where('produit_id', $produitId)
            ->where('depot_id', $depotId)
            ->nonPerime()
            ->fefo()
            ->get();
    }

    public function getTotalDisponible(int $produitId, int $depotId): int
    {
        return (int) Stock::where('produit_id', $produitId)
            ->where('depot_id', $depotId)
            ->nonPerime()
            ->sum('quantite');
    }

    /**
     * ✅ CORRIGÉ : Upsert stock avec TOUS les nouveaux champs
     */
    public function upsertStock(array $data): Stock
    {
        // Chercher le stock existant
        $stock = Stock::where([
            'produit_id' => $data['produit_id'],
            'depot_id' => $data['depot_id'],
            'numero_lot' => $data['numero_lot'],
            'date_peremption' => $data['date_peremption'],
        ])->first();

        if ($stock) {
            // ✅ MISE À JOUR : Incrémenter la quantité
            $stock->increment('quantite', (int)$data['quantite']);
            
            // ✅ Mettre à jour les autres champs (prix peuvent changer)
            $stock->update([
                'prix_achat' => $data['prix_achat'] ?? $stock->prix_achat,
                'prix_achat_unitaire_ht' => $data['prix_achat_unitaire_ht'] ?? $stock->prix_achat_unitaire_ht,
                'prix_achat_unitaire_ttc' => $data['prix_achat_unitaire_ttc'] ?? $stock->prix_achat_unitaire_ttc,
                'taux_tva' => $data['taux_tva'] ?? $stock->taux_tva,
                'montant_tva_unitaire' => $data['montant_tva_unitaire'] ?? $stock->montant_tva_unitaire,
                'prix_vente_unitaire_ht' => $data['prix_vente_unitaire_ht'] ?? $stock->prix_vente_unitaire_ht,
                'prix_vente_unitaire_ttc' => $data['prix_vente_unitaire_ttc'] ?? $stock->prix_vente_unitaire_ttc,
                'marge_unitaire_ht' => $data['marge_unitaire_ht'] ?? $stock->marge_unitaire_ht,
                'marge_unitaire_ttc' => $data['marge_unitaire_ttc'] ?? $stock->marge_unitaire_ttc,
                'taux_marge' => $data['taux_marge'] ?? $stock->taux_marge,
            ]);
        } else {
            // ✅ CRÉATION : Nouveau stock avec tous les champs
            $stock = Stock::create([
                'produit_id' => $data['produit_id'],
                'depot_id' => $data['depot_id'],
                'numero_lot' => $data['numero_lot'],
                'date_peremption' => $data['date_peremption'],
                'quantite' => (int)$data['quantite'],
                
                // Ancienne colonne
                'prix_achat' => $data['prix_achat'] ?? 0,
                
                // ✅ NOUVEAUX CHAMPS
                'prix_achat_unitaire_ht' => $data['prix_achat_unitaire_ht'] ?? null,
                'prix_achat_unitaire_ttc' => $data['prix_achat_unitaire_ttc'] ?? null,
                'taux_tva' => $data['taux_tva'] ?? 18.9,
                'montant_tva_unitaire' => $data['montant_tva_unitaire'] ?? null,
                'prix_vente_unitaire_ht' => $data['prix_vente_unitaire_ht'] ?? null,
                'prix_vente_unitaire_ttc' => $data['prix_vente_unitaire_ttc'] ?? null,
                'marge_unitaire_ht' => $data['marge_unitaire_ht'] ?? null,
                'marge_unitaire_ttc' => $data['marge_unitaire_ttc'] ?? null,
                'taux_marge' => $data['taux_marge'] ?? null,
            ]);
        }

        return $stock->fresh(); // Recharger pour avoir les données à jour
    }

    public function decrementerStock(int $stockId, int $quantite): void
    {
        Stock::where('id', $stockId)->decrement('quantite', $quantite);
    }

    public function incrementerStock(int $stockId, int $quantite): void
    {
        Stock::where('id', $stockId)->increment('quantite', $quantite);
    }

    public function getStocksPerimes()
    {
        return Stock::with(['produit', 'depot'])
            ->perime()
            ->where('quantite', '>', 0)
            ->get();
    }

    public function getStocksProches(int $jours = 30)
    {
        return Stock::with(['produit', 'depot'])
            ->proche($jours)
            ->where('quantite', '>', 0)
            ->get();
    }

    public function getStocksBon(int $jours = 30)
    {
        return Stock::with(['produit', 'depot'])
            ->bon($jours)
            ->where('quantite', '>', 0)
            ->get();
    }

    /**
     * ✅ Seuils min/max pro :
     * - utilise seuil_stocks si défini
     * - sinon fallback: min=5 max=200
     * - exclut quantite<=0 + lots périmés
     * - agrège par produit + dépôt
     */
    public function verifierSeuils(): Collection
    {
        $DEFAULT_MIN = 5;
        $DEFAULT_MAX = 200;

        return DB::table('stocks as s')
            ->leftJoin('seuil_stocks as ss', function ($join) {
                $join->on('s.produit_id', '=', 'ss.produit_id')
                    ->on('s.depot_id', '=', 'ss.depot_id');
            })
            ->leftJoin('produits as p', 'p.id', '=', 's.produit_id')
            ->leftJoin('depots as d', 'd.id', '=', 's.depot_id')
            ->where('s.quantite', '>', 0)
            ->whereDate('s.date_peremption', '>=', now()->toDateString()) // ✅ exclut expirés
            ->groupBy('s.produit_id', 's.depot_id', 'p.code', 'p.nom', 'd.code', 'd.libelle')
            ->selectRaw('
                s.produit_id,
                s.depot_id,
                SUM(s.quantite) as quantite_totale,
                COALESCE(ss.seuil_min, ?) as seuil_min,
                COALESCE(ss.seuil_max, ?) as seuil_max,
                p.code as produit_code,
                p.nom as produit_nom,
                d.code as depot_code,
                d.libelle as depot_libelle
            ', [$DEFAULT_MIN, $DEFAULT_MAX])
            ->get();
    }
}