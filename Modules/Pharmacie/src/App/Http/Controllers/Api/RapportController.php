<?php

namespace Modules\Pharmacie\App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pharmacie\App\Interfaces\RapportInterface;
use Modules\Pharmacie\App\Interfaces\StockInterface;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Pharmacie\App\Exports\VentesExport;
use Modules\Pharmacie\App\Exports\StocksExport;
use Modules\Pharmacie\App\Exports\AlertesExport;

class RapportController extends Controller
{
    public function __construct(
        private RapportInterface $rapportService,
        private StockInterface $stockService  // ← AJOUTÉ
    ) {}

    /**
     * Rapport ventes du jour
     */
    public function ventesJour(Request $request): JsonResponse
    {
        $date = $request->get('date', now()->toDateString());
        $rapport = $this->rapportService->ventesJour($date);

        return response()->json([
            'success' => true,
            'message' => 'Rapport ventes du jour',
            'data' => $rapport
        ], 200);
    }

    /**
     * Rapport ventes de la semaine
     */
    public function ventesSemaine(Request $request): JsonResponse
    {
        $dateDebut = $request->get('date_debut', now()->startOfWeek()->toDateString());
        $dateFin = $request->get('date_fin', now()->endOfWeek()->toDateString());
        $rapport = $this->rapportService->ventesSemaine($dateDebut, $dateFin);

        return response()->json([
            'success' => true,
            'message' => 'Rapport ventes de la semaine',
            'data' => $rapport
        ], 200);
    }

    /**
     * Rapport ventes du mois
     */
    public function ventesMois(Request $request): JsonResponse
    {
        $annee = $request->get('annee', now()->year);
        $mois = $request->get('mois', now()->month);
        $rapport = $this->rapportService->ventesMois($annee, $mois);

        return response()->json([
            'success' => true,
            'message' => 'Rapport ventes du mois',
            'data' => $rapport
        ], 200);
    }

    /**
     * Rapport stock restant + valeur TTC
     */
    public function stockRestant(): JsonResponse
    {
        $rapport = $this->rapportService->stockRestant();

        return response()->json([
            'success' => true,
            'message' => 'Rapport stock restant',
            'data' => $rapport
        ], 200);
    }

    // ========================================
    // EXPORTS EXCEL
    // ========================================

    /**
     * Export Excel ventes du jour
     */
    public function exportVentesJour(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $rapport = $this->rapportService->ventesJour($date);
        
        $filename = 'ventes_jour_' . $date . '.xlsx';
        
        return Excel::download(
            new VentesExport($rapport['ventes'], 'Ventes du ' . $date),
            $filename
        );
    }

    /**
     * Export Excel ventes de la semaine
     */
    public function exportVentesSemaine(Request $request)
    {
        $dateDebut = $request->get('date_debut', now()->startOfWeek()->toDateString());
        $dateFin = $request->get('date_fin', now()->endOfWeek()->toDateString());
        $rapport = $this->rapportService->ventesSemaine($dateDebut, $dateFin);
        
        $filename = 'ventes_semaine_' . $dateDebut . '_' . $dateFin . '.xlsx';
        
        return Excel::download(
            new VentesExport($rapport['ventes'], 'Ventes semaine'),
            $filename
        );
    }

    /**
     * Export Excel ventes du mois
     */
    public function exportVentesMois(Request $request)
    {
        $annee = $request->get('annee', now()->year);
        $mois = $request->get('mois', now()->month);
        $rapport = $this->rapportService->ventesMois($annee, $mois);
        
        $filename = 'ventes_mois_' . $annee . '_' . str_pad($mois, 2, '0', STR_PAD_LEFT) . '.xlsx';
        
        return Excel::download(
            new VentesExport($rapport['ventes'], $rapport['periode']),
            $filename
        );
    }

    /**
     * Export Excel des alertes stocks
     */
    public function exportAlertes()
    {
        $filename = 'alertes_stocks_' . now()->format('Y-m-d') . '.xlsx';
        
        return Excel::download(
            new AlertesExport($this->stockService),
            $filename
        );
    }

    /**
     * Export Excel stock complet
     */
    public function exportStocksComplet()
    {
        $stocks = \Modules\Pharmacie\App\Models\Stock::with(['produit', 'depot'])
            ->where('quantite', '>', 0)
            ->orderBy('date_peremption', 'asc')
            ->get();
        
        $filename = 'stocks_complet_' . now()->format('Y-m-d') . '.xlsx';
        
        return Excel::download(
            new StocksExport($stocks, 'Stock Complet'),
            $filename
        );
    }
}