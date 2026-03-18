<?php

namespace Modules\Pharmacie\App\Services;

use Modules\Pharmacie\App\Models\Produit;
use Modules\Pharmacie\App\Models\Stock;
use Barryvdh\DomPDF\Facade\Pdf;
use Milon\Barcode\DNS1D;

class EtiquetteService
{
    /**
     * Générer étiquette produit avec code-barres
     */
    public function genererEtiquetteProduit(int $produitId, int $quantite = 1): string
    {
        $produit = Produit::findOrFail($produitId);
        
        // Générer le code-barres
        $barcode = new DNS1D();
        $barcodeImage = $barcode->getBarcodePNG($produit->code, 'C128', 3, 50);

        $html = view('pharmacie::etiquettes.produit', [
            'produit' => $produit,
            'barcode' => $barcodeImage,
            'quantite' => $quantite,
        ])->render();

        $pdf = PDF::loadHTML($html);
        $pdf->setPaper([0, 0, 226.77, 141.73], 'landscape'); // 80mm x 50mm

        return $pdf->output();
    }

    /**
     * Générer étiquette lot (avec péremption)
     */
    public function genererEtiquetteLot(int $stockId): string
    {
        $stock = Stock::with(['produit', 'depot'])->findOrFail($stockId);
        
        // Code-barres pour le lot
        $barcode = new DNS1D();
        $barcodeImage = $barcode->getBarcodePNG($stock->numero_lot, 'C128', 2, 40);

        $html = view('pharmacie::etiquettes.lot', [
            'stock' => $stock,
            'barcode' => $barcodeImage,
        ])->render();

        $pdf = PDF::loadHTML($html);
        $pdf->setPaper([0, 0, 226.77, 141.73], 'landscape');

        return $pdf->output();
    }

    /**
     * Générer étiquettes en masse pour une réception
     */
    public function genererEtiquettesReception(int $receptionId): string
    {
        $reception = \Modules\Pharmacie\App\Models\Reception::with(['lignes.produit', 'lignes.depot'])
            ->findOrFail($receptionId);

        $etiquettes = [];

        foreach ($reception->lignes as $ligne) {
            // Trouver le stock correspondant
            $stock = Stock::where('produit_id', $ligne->produit_id)
                ->where('depot_id', $ligne->depot_id)
                ->where('numero_lot', $ligne->numero_lot)
                ->where('date_peremption', $ligne->date_peremption)
                ->first();

            if ($stock) {
                $barcode = new DNS1D();
                $barcodeImage = $barcode->getBarcodePNG($stock->numero_lot, 'C128', 2, 40);

                $etiquettes[] = [
                    'stock' => $stock,
                    'barcode' => $barcodeImage,
                    'quantite' => $ligne->quantite,
                ];
            }
        }

        $html = view('pharmacie::etiquettes.reception', [
            'reception' => $reception,
            'etiquettes' => $etiquettes,
        ])->render();

        $pdf = PDF::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->output();
    }

    /**
     * Générer étiquette rayonnage (pour localisation)
     */
    public function genererEtiquetteRayonnage(string $code, string $localisation): string
    {
        $barcode = new DNS1D();
        $barcodeImage = $barcode->getBarcodePNG($code, 'C128', 3, 60);

        $html = view('pharmacie::etiquettes.rayonnage', [
            'code' => $code,
            'localisation' => $localisation,
            'barcode' => $barcodeImage,
        ])->render();

        $pdf = PDF::loadHTML($html);
        $pdf->setPaper([0, 0, 283.46, 141.73], 'landscape'); // 100mm x 50mm

        return $pdf->output();
    }
}