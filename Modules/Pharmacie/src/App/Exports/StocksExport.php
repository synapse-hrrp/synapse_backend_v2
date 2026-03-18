<?php

namespace Modules\Pharmacie\App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StocksExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
    protected $stocks;
    protected $titre;

    public function __construct($stocks, $titre = 'Rapport Stocks')
    {
        $this->stocks = $stocks;
        $this->titre = $titre;
    }

    public function collection()
    {
        return $this->stocks;
    }

    public function headings(): array
    {
        return [
            'Produit',
            'Dépôt',
            'N° Lot',
            'Date Péremption',
            'Quantité',
            'Prix Achat',
            'Valeur Totale',
            'Statut',
        ];
    }

    public function map($stock): array
    {
        $datePeremption = \Carbon\Carbon::parse($stock->date_peremption);
        $joursRestants = now()->diffInDays($datePeremption, false);
        
        if ($joursRestants < 0) {
            $statut = 'PÉRIMÉ';
        } elseif ($joursRestants <= 30) {
            $statut = 'PROCHE (' . $joursRestants . 'j)';
        } else {
            $statut = 'BON';
        }

        return [
            $stock->produit->nom,
            $stock->depot->libelle,
            $stock->numero_lot,
            $datePeremption->format('d/m/Y'),
            $stock->quantite,
            number_format($stock->prix_achat, 0, ',', ' ') . ' FCFA',
            number_format($stock->quantite * $stock->prix_achat, 0, ',', ' ') . ' FCFA',
            $statut,
        ];
    }

    public function title(): string
    {
        return substr($this->titre, 0, 31);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}