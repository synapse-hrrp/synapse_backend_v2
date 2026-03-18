<?php

namespace Modules\Pharmacie\App\Services;

use Modules\Pharmacie\App\Models\Commande;
use Modules\Pharmacie\App\Models\Reception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv as CsvWriter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ExportService
{
    /**
     * Exporter une commande (Excel, CSV ou PDF)
     */
    public function exporterCommande(int $commandeId, string $format = 'EXCEL', int $userId = null): array
    {
        // ✅ SOLUTION A: Créer dossier dans public
        $directory = storage_path('app/public/exports/commandes');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $commande = Commande::with(['lignes.produit', 'fournisseur', 'depot'])->findOrFail($commandeId);

        $filename = "commande_{$commande->numero}_" . now()->format('Ymd_His');

        $format = strtoupper($format);

        switch ($format) {
            case 'EXCEL':
                $path = $this->exporterCommandeExcel($commande, $filename);
                break;
            case 'CSV':
                $path = $this->exporterCommandeCsv($commande, $filename);
                break;
            case 'PDF':
                $path = $this->exporterCommandePdf($commande, $filename);
                break;
            default:
                throw new \Exception("Format non supporté: $format");
        }

        // ✅ Enlever 'public/' du path pour la BDD
        $pathBDD = str_replace('public/', '', $path);

        $commande->update([
            'fichier_export_path' => $pathBDD,
            'format_export' => $format,
            'exporte_at' => now(),
            'exporte_par_user_id' => $userId,
        ]);

        return [
            'path' => $pathBDD,
            'filename' => basename($path),
            'format' => $format,
        ];
    }

    /**
     * Exporter une réception (Excel, CSV ou PDF)
     */
    public function exporterReception(int $receptionId, string $format = 'EXCEL', int $userId = null): array
    {
        // ✅ SOLUTION A: Créer dossier dans public
        $directory = storage_path('app/public/exports/receptions');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $reception = Reception::with(['lignes.produit', 'lignes.depot', 'fournisseur', 'depot'])->findOrFail($receptionId);

        $filename = "reception_{$reception->numero}_" . now()->format('Ymd_His');

        $format = strtoupper($format);

        switch ($format) {
            case 'EXCEL':
                $path = $this->exporterReceptionExcel($reception, $filename);
                break;
            case 'CSV':
                $path = $this->exporterReceptionCsv($reception, $filename);
                break;
            case 'PDF':
                $path = $this->exporterReceptionPdf($reception, $filename);
                break;
            default:
                throw new \Exception("Format non supporté: $format");
        }

        // ✅ Enlever 'public/' du path pour la BDD
        $pathBDD = str_replace('public/', '', $path);

        $reception->update([
            'fichier_export_path' => $pathBDD,
            'format_export' => $format,
            'exporte_at' => now(),
            'exporte_par_user_id' => $userId,
        ]);

        return [
            'path' => $pathBDD,
            'filename' => basename($path),
            'format' => $format,
        ];
    }

    // ========================================
    // COMMANDE - EXCEL
    // ========================================
    private function exporterCommandeExcel(Commande $commande, string $filename): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // En-tête
        $sheet->setCellValue('A1', 'BON DE COMMANDE');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        // Informations commande
        $sheet->setCellValue('A3', 'Numéro:');
        $sheet->setCellValue('B3', $commande->numero);
        $sheet->setCellValue('A4', 'Date:');
        $sheet->setCellValue('B4', Carbon::parse($commande->date_commande)->format('d/m/Y'));
        $sheet->setCellValue('A5', 'Fournisseur:');
        $sheet->setCellValue('B5', $commande->fournisseur->nom ?? 'N/A');
        $sheet->setCellValue('A6', 'Statut:');
        $sheet->setCellValue('B6', $commande->statut);
        $sheet->setCellValue('A7', 'Type:');
        $sheet->setCellValue('B7', $commande->type ?? 'MANUELLE');

        // Headers lignes
        $row = 9;
        $sheet->setCellValue('A' . $row, 'Code');
        $sheet->setCellValue('B' . $row, 'Produit');
        $sheet->setCellValue('C' . $row, 'Quantité commandée');
        $sheet->setCellValue('D' . $row, 'Quantité reçue');
        $sheet->setCellValue('E' . $row, 'Prix unitaire');
        $sheet->setCellValue('F' . $row, 'Montant total');
        $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':F' . $row)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Lignes produits
        $row++;
        $montantTotal = 0;

        foreach ($commande->lignes as $ligne) {
            $montantLigne = $ligne->quantite_commandee * $ligne->prix_unitaire;
            $montantTotal += $montantLigne;

            $sheet->setCellValue('A' . $row, $ligne->produit->code);
            $sheet->setCellValue('B' . $row, $ligne->produit->nom);
            $sheet->setCellValue('C' . $row, $ligne->quantite_commandee);
            $sheet->setCellValue('D' . $row, $ligne->quantite_recue);
            $sheet->setCellValue('E' . $row, number_format($ligne->prix_unitaire, 2));
            $sheet->setCellValue('F' . $row, number_format($montantLigne, 2));
            $row++;
        }

        // Total
        $row++;
        $sheet->setCellValue('E' . $row, 'TOTAL:');
        $sheet->setCellValue('F' . $row, number_format($montantTotal, 2) . ' FCFA');
        $sheet->getStyle('E' . $row . ':F' . $row)->getFont()->setBold(true);

        // Auto-size colonnes
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ✅ Sauvegarder dans public/exports
        $path = "public/exports/commandes/{$filename}.xlsx";
        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path("app/{$path}"));

        return $path;
    }

    // ========================================
    // COMMANDE - CSV
    // ========================================
    private function exporterCommandeCsv(Commande $commande, string $filename): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->setCellValue('A1', 'Code');
        $sheet->setCellValue('B1', 'Produit');
        $sheet->setCellValue('C1', 'Quantité commandée');
        $sheet->setCellValue('D1', 'Quantité reçue');
        $sheet->setCellValue('E1', 'Prix unitaire');
        $sheet->setCellValue('F1', 'Montant total');

        // Lignes
        $row = 2;
        foreach ($commande->lignes as $ligne) {
            $sheet->setCellValue('A' . $row, $ligne->produit->code);
            $sheet->setCellValue('B' . $row, $ligne->produit->nom);
            $sheet->setCellValue('C' . $row, $ligne->quantite_commandee);
            $sheet->setCellValue('D' . $row, $ligne->quantite_recue);
            $sheet->setCellValue('E' . $row, $ligne->prix_unitaire);
            $sheet->setCellValue('F' . $row, $ligne->quantite_commandee * $ligne->prix_unitaire);
            $row++;
        }

        // ✅ Sauvegarder dans public/exports
        $path = "public/exports/commandes/{$filename}.csv";
        $writer = new CsvWriter($spreadsheet);
        $writer->setDelimiter(';');
        $writer->setEnclosure('"');
        $writer->save(storage_path("app/{$path}"));

        return $path;
    }

    // ========================================
    // COMMANDE - PDF
    // ========================================
    private function exporterCommandePdf(Commande $commande, string $filename): string
    {
        $pdf = Pdf::loadView('pharmacie::exports.commande_pdf', [
            'commande' => $commande,
        ]);

        // ✅ Sauvegarder dans public/exports
        $path = "public/exports/commandes/{$filename}.pdf";
        Storage::put($path, $pdf->output());

        return $path;
    }

    // ========================================
    // RÉCEPTION - EXCEL
    // ========================================
    private function exporterReceptionExcel(Reception $reception, string $filename): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // En-tête
        $sheet->setCellValue('A1', 'BON DE RÉCEPTION');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        // Informations réception
        $sheet->setCellValue('A3', 'Numéro:');
        $sheet->setCellValue('B3', $reception->numero);
        $sheet->setCellValue('A4', 'Date:');
        $sheet->setCellValue('B4', Carbon::parse($reception->date_reception)->format('d/m/Y'));
        $sheet->setCellValue('A5', 'Fournisseur:');
        $sheet->setCellValue('B5', $reception->fournisseur->nom ?? 'N/A');

        // Headers
        $row = 7;
        $headers = ['Code', 'Produit', 'Dépôt', 'Quantité', 'Lot', 'Péremption', 'Prix achat HT', 'Montant HT'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, $row, $header);
        }
        $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':H' . $row)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Lignes
        $row++;
        foreach ($reception->lignes as $ligne) {
            $sheet->setCellValue('A' . $row, $ligne->produit->code);
            $sheet->setCellValue('B' . $row, $ligne->produit->nom);
            $sheet->setCellValue('C' . $row, $ligne->depot->code ?? 'N/A');
            $sheet->setCellValue('D' . $row, $ligne->quantite_recue ?? $ligne->quantite);
            $sheet->setCellValue('E' . $row, $ligne->numero_lot);
            $sheet->setCellValue('F' . $row, Carbon::parse($ligne->date_peremption)->format('d/m/Y'));
            $sheet->setCellValue('G' . $row, number_format($ligne->prix_achat_unitaire_ht ?? 0, 2));
            $sheet->setCellValue('H' . $row, number_format($ligne->montant_achat_ht ?? 0, 2));
            $row++;
        }

        // Total
        $row++;
        $sheet->setCellValue('G' . $row, 'TOTAL HT:');
        $sheet->setCellValue('H' . $row, number_format($reception->montant_total_ht ?? 0, 2) . ' FCFA');
        $sheet->getStyle('G' . $row . ':H' . $row)->getFont()->setBold(true);
        
        $row++;
        $sheet->setCellValue('G' . $row, 'TOTAL TTC:');
        $sheet->setCellValue('H' . $row, number_format($reception->montant_total_ttc ?? 0, 2) . ' FCFA');
        $sheet->getStyle('G' . $row . ':H' . $row)->getFont()->setBold(true);

        // Auto-size
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ✅ Sauvegarder dans public/exports
        $path = "public/exports/receptions/{$filename}.xlsx";
        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path("app/{$path}"));

        return $path;
    }

    // ========================================
    // RÉCEPTION - CSV
    // ========================================
    private function exporterReceptionCsv(Reception $reception, string $filename): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = ['Code', 'Produit', 'Dépôt', 'Quantité', 'Lot', 'Péremption', 'Prix achat HT', 'Montant HT'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        // Lignes
        $row = 2;
        foreach ($reception->lignes as $ligne) {
            $sheet->setCellValue('A' . $row, $ligne->produit->code);
            $sheet->setCellValue('B' . $row, $ligne->produit->nom);
            $sheet->setCellValue('C' . $row, $ligne->depot->code ?? 'N/A');
            $sheet->setCellValue('D' . $row, $ligne->quantite_recue ?? $ligne->quantite);
            $sheet->setCellValue('E' . $row, $ligne->numero_lot);
            $sheet->setCellValue('F' . $row, $ligne->date_peremption);
            $sheet->setCellValue('G' . $row, $ligne->prix_achat_unitaire_ht ?? 0);
            $sheet->setCellValue('H' . $row, $ligne->montant_achat_ht ?? 0);
            $row++;
        }

        // ✅ Sauvegarder dans public/exports
        $path = "public/exports/receptions/{$filename}.csv";
        $writer = new CsvWriter($spreadsheet);
        $writer->setDelimiter(';');
        $writer->save(storage_path("app/{$path}"));

        return $path;
    }

    // ========================================
    // RÉCEPTION - PDF
    // ========================================
    private function exporterReceptionPdf(Reception $reception, string $filename): string
    {
        $pdf = Pdf::loadView('pharmacie::exports.reception_pdf', [
            'reception' => $reception,
        ]);

        // ✅ Sauvegarder dans public/exports
        $path = "public/exports/receptions/{$filename}.pdf";
        Storage::put($path, $pdf->output());

        return $path;
    }
}