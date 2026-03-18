<?php

namespace Modules\Pharmacie\App\Repository;

use Modules\Pharmacie\App\Models\Vente;
use Modules\Pharmacie\App\Models\Stock;
use Illuminate\Support\Facades\DB;

class RapportRepository
{
    /**
     * Ventes par période
     */
    public function getVentesPeriode(string $dateDebut, string $dateFin)
    {
        return Vente::with(['lignes.produit', 'depot'])
            ->whereBetween('date_vente', [$dateDebut, $dateFin])
            ->where('statut', '!=', 'ANNULEE')
            ->get();
    }

    /**
     * Total ventes par période
     */
    public function getTotalVentesPeriode(string $dateDebut, string $dateFin): float
    {
        return Vente::whereBetween('date_vente', [$dateDebut, $dateFin])
            ->where('statut', '!=', 'ANNULEE')
            ->sum('montant_ttc');
    }

    /**
     * Stock restant avec valeur
     */
    public function getStockRestant()
    {
        return Stock::with(['produit', 'depot'])
            ->select(
                'produit_id',
                'depot_id',
                DB::raw('SUM(quantite) as quantite_totale'),
                DB::raw('SUM(quantite * prix_achat * 1.40) as valeur_ttc')
            )
            ->where('quantite', '>', 0)
            ->groupBy('produit_id', 'depot_id')
            ->get();
    }
}