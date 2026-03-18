<?php

namespace Modules\Pharmacie\App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Pharmacie\App\Services\EtiquetteService;

class EtiquetteController extends Controller
{
    public function __construct(
        private EtiquetteService $etiquetteService
    ) {}

    /**
     * Générer étiquette produit
     */
    public function produit(Request $request, int $produitId)
    {
        $quantite = $request->get('quantite', 1);
        
        $pdf = $this->etiquetteService->genererEtiquetteProduit($produitId, $quantite);
        
        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="etiquette_produit_' . $produitId . '.pdf"');
    }

    /**
     * Générer étiquette lot
     */
    public function lot(int $stockId)
    {
        $pdf = $this->etiquetteService->genererEtiquetteLot($stockId);
        
        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="etiquette_lot_' . $stockId . '.pdf"');
    }

    /**
     * Générer étiquettes pour une réception
     */
    public function reception(int $receptionId)
    {
        $pdf = $this->etiquetteService->genererEtiquettesReception($receptionId);
        
        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="etiquettes_reception_' . $receptionId . '.pdf"');
    }

    /**
     * Générer étiquette rayonnage
     */
    public function rayonnage(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'localisation' => 'required|string',
        ]);

        $pdf = $this->etiquetteService->genererEtiquetteRayonnage(
            $request->code,
            $request->localisation
        );
        
        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="etiquette_rayonnage.pdf"');
    }
}