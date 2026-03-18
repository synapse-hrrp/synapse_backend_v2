<?php

namespace Modules\Pharmacie\App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VentesExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
    protected $ventes;
    protected $titre;

    public function __construct($ventes, $titre = 'Rapport Ventes')
    {
        $this->ventes = $ventes;
        $this->titre = $titre;
    }

    public function collection()
    {
        return $this->ventes;
    }

    public function headings(): array
    {
        return [
            'Numéro',
            'Date',
            'Dépôt',
            'Type',
            'Statut',
            'Montant TTC',
            'Vendeur',
            'Observations',
        ];
    }

    public function map($vente): array
    {
        return [
            $vente->numero,
            $vente->date_vente->format('d/m/Y'),
            $vente->depot->libelle,
            $vente->type,
            $vente->statut,
            number_format($vente->montant_ttc, 0, ',', ' ') . ' FCFA',
            $vente->user ? $vente->user->name : 'N/A',
            $vente->observations ?? '',
        ];
    }

    public function title(): string
    {
        return substr($this->titre, 0, 31); // Excel limite à 31 caractères
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}