<?php

namespace Modules\Pharmacie\App\Services;

use Modules\Pharmacie\App\Repository\RapportRepository;
use Modules\Pharmacie\App\Repository\StockRepository;
use Modules\Pharmacie\App\Models\Vente;
use Modules\Pharmacie\App\Models\Stock;
use Modules\Pharmacie\App\Models\Produit;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(
        private RapportRepository $rapportRepository,
        private StockRepository $stockRepository
    ) {}

    /**
     * Dashboard complet
     */
    public function getDashboard(): array
    {
        return [
            'kpi' => $this->getKPI(),
            'chiffre_affaires' => $this->getChiffreAffaires(),
            'top_produits' => $this->getTopProduits(),
            'evolution_ventes' => $this->getEvolutionVentes(),
            'alertes' => $this->getAlertes(),
            'repartition_depots' => $this->getRepartitionDepots(),
        ];
    }

    /**
     * KPI (Indicateurs clés)
     */
    private function getKPI(): array
    {
        // CA du jour
        $caJour = Vente::whereDate('date_vente', today())
            ->where('statut', '!=', 'ANNULEE')
            ->sum('montant_ttc');

        // CA du mois
        $caMois = Vente::whereYear('date_vente', now()->year)
            ->whereMonth('date_vente', now()->month)
            ->where('statut', '!=', 'ANNULEE')
            ->sum('montant_ttc');

        // Nombre de ventes aujourd'hui
        $ventesJour = Vente::whereDate('date_vente', today())
            ->where('statut', '!=', 'ANNULEE')
            ->count();

        // Valeur totale du stock
        $valeurStock = Stock::where('quantite', '>', 0)
            ->selectRaw('SUM(quantite * prix_achat * 1.40) as valeur')
            ->value('valeur');

        // Nombre de produits en stock
        $produitsEnStock = Stock::where('quantite', '>', 0)
            ->distinct('produit_id')
            ->count('produit_id');

        // Stocks périmés
        $stocksPerimes = Stock::where('date_peremption', '<', today())
            ->where('quantite', '>', 0)
            ->count();

        // Stocks proches (30j)
        $stocksProches = Stock::whereBetween('date_peremption', [
            today(),
            now()->addDays(30)
        ])->where('quantite', '>', 0)->count();

        return [
            'ca_jour' => round($caJour, 2),
            'ca_mois' => round($caMois, 2),
            'ventes_jour' => $ventesJour,
            'valeur_stock' => round($valeurStock ?? 0, 2),
            'produits_en_stock' => $produitsEnStock,
            'alertes_perimes' => $stocksPerimes,
            'alertes_proches' => $stocksProches,
        ];
    }

    /**
     * Chiffre d'affaires par période
     */
    private function getChiffreAffaires(): array
    {
        return [
            'jour' => $this->rapportRepository->getTotalVentesPeriode(
                today()->toDateString(),
                today()->toDateString()
            ),
            'semaine' => $this->rapportRepository->getTotalVentesPeriode(
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString()
            ),
            'mois' => $this->rapportRepository->getTotalVentesPeriode(
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString()
            ),
            'annee' => $this->rapportRepository->getTotalVentesPeriode(
                now()->startOfYear()->toDateString(),
                now()->endOfYear()->toDateString()
            ),
        ];
    }

    /**
     * Top 10 produits les plus vendus
     */
    private function getTopProduits(int $limit = 10): array
    {
        return DB::table('ligne_ventes')
            ->join('produits', 'ligne_ventes.produit_id', '=', 'produits.id')
            ->join('ventes', 'ligne_ventes.vente_id', '=', 'ventes.id')
            ->where('ventes.statut', '!=', 'ANNULEE')
            ->select(
                'produits.id',
                'produits.nom',
                'produits.code',
                DB::raw('SUM(ligne_ventes.quantite) as total_vendu'),
                DB::raw('SUM(ligne_ventes.montant_ligne_ttc) as ca_total')
            )
            ->groupBy('produits.id', 'produits.nom', 'produits.code')
            ->orderByDesc('total_vendu')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'produit_id' => $item->id,
                    'nom' => $item->nom,
                    'code' => $item->code,
                    'quantite_vendue' => (int) $item->total_vendu,
                    'chiffre_affaires' => round($item->ca_total, 2),
                ];
            })
            ->toArray();
    }

    /**
     * Évolution des ventes (30 derniers jours)
     */
    private function getEvolutionVentes(int $jours = 30): array
    {
        $data = [];
        
        for ($i = $jours - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            
            $ca = Vente::whereDate('date_vente', $date)
                ->where('statut', '!=', 'ANNULEE')
                ->sum('montant_ttc');

            $nbVentes = Vente::whereDate('date_vente', $date)
                ->where('statut', '!=', 'ANNULEE')
                ->count();

            $data[] = [
                'date' => $date,
                'date_formatted' => Carbon::parse($date)->format('d/m'),
                'ca' => round($ca, 2),
                'nombre_ventes' => $nbVentes,
            ];
        }

        return $data;
    }

    /**
     * Alertes en temps réel
     */
    private function getAlertes(): array
    {
        // Stocks périmés
        $perimes = $this->stockRepository->getStocksPerimes();

        // Stocks proches péremption
        $proches = $this->stockRepository->getStocksProches(30);

        // Seuils
        $seuils = $this->stockRepository->verifierSeuils();

        $sousMin = $seuils->filter(fn($s) => $s->quantite_totale < $s->seuil_min);
        $surMax = $seuils->filter(fn($s) => $s->quantite_totale > $s->seuil_max);

        return [
            'stocks_perimes' => [
                'count' => $perimes->count(),
                'items' => $perimes->take(5)->map(fn($s) => [
                    'produit' => $s->produit->nom,
                    'lot' => $s->numero_lot,
                    'quantite' => $s->quantite,
                    'perime_depuis' => now()->diffInDays($s->date_peremption) . ' jours',
                ]),
            ],
            'stocks_proches' => [
                'count' => $proches->count(),
                'items' => $proches->take(5)->map(fn($s) => [
                    'produit' => $s->produit->nom,
                    'lot' => $s->numero_lot,
                    'quantite' => $s->quantite,
                    'expire_dans' => now()->diffInDays($s->date_peremption) . ' jours',
                ]),
            ],
            'sous_seuil_min' => [
                'count' => $sousMin->count(),
                'items' => $sousMin->take(5)->values(),
            ],
            'sur_seuil_max' => [
                'count' => $surMax->count(),
                'items' => $surMax->take(5)->values(),
            ],
        ];
    }

    /**
     * Répartition du stock par dépôt
     */
    private function getRepartitionDepots(): array
    {
        return DB::table('stocks')
            ->join('depots', 'stocks.depot_id', '=', 'depots.id')
            ->where('stocks.quantite', '>', 0)
            ->select(
                'depots.code',
                'depots.libelle',
                DB::raw('COUNT(DISTINCT stocks.produit_id) as nb_produits'),
                DB::raw('SUM(stocks.quantite) as quantite_totale'),
                DB::raw('SUM(stocks.quantite * stocks.prix_achat * 1.40) as valeur_ttc')
            )
            ->groupBy('depots.id', 'depots.code', 'depots.libelle')
            ->get()
            ->map(fn($d) => [
                'depot' => $d->code,
                'libelle' => $d->libelle,
                'nb_produits' => $d->nb_produits,
                'quantite_totale' => $d->quantite_totale,
                'valeur_ttc' => round($d->valeur_ttc, 2),
            ])
            ->toArray();
    }
}