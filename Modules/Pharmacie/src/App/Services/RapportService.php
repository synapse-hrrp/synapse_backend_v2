<?php

namespace Modules\Pharmacie\App\Services;

use Modules\Pharmacie\App\Interfaces\RapportInterface;
use Modules\Pharmacie\App\Repository\RapportRepository;
use Carbon\Carbon;

class RapportService implements RapportInterface
{
    public function __construct(
        private RapportRepository $rapportRepository
    ) {}

    /**
     * Rapport ventes du jour
     */
    public function ventesJour(string $date)
    {
        $ventes = $this->rapportRepository->getVentesPeriode($date, $date);
        $total = $this->rapportRepository->getTotalVentesPeriode($date, $date);

        return [
            'date' => $date,
            'ventes' => $ventes,
            'total_ttc' => $total,
            'nombre_ventes' => $ventes->count(),
        ];
    }

    /**
     * Rapport ventes de la semaine
     */
    public function ventesSemaine(string $dateDebut, string $dateFin)
    {
        $ventes = $this->rapportRepository->getVentesPeriode($dateDebut, $dateFin);
        $total = $this->rapportRepository->getTotalVentesPeriode($dateDebut, $dateFin);

        return [
            'periode' => "Du $dateDebut au $dateFin",
            'ventes' => $ventes,
            'total_ttc' => $total,
            'nombre_ventes' => $ventes->count(),
        ];
    }

    /**
     * Rapport ventes du mois
     */
    public function ventesMois(int $annee, int $mois)
    {
        $dateDebut = Carbon::createFromDate($annee, $mois, 1)->startOfMonth()->toDateString();
        $dateFin = Carbon::createFromDate($annee, $mois, 1)->endOfMonth()->toDateString();

        $ventes = $this->rapportRepository->getVentesPeriode($dateDebut, $dateFin);
        $total = $this->rapportRepository->getTotalVentesPeriode($dateDebut, $dateFin);

        return [
            'periode' => Carbon::createFromDate($annee, $mois, 1)->format('F Y'),
            'ventes' => $ventes,
            'total_ttc' => $total,
            'nombre_ventes' => $ventes->count(),
        ];
    }

    /**
     * Rapport stock restant + valeur TTC
     */
    public function stockRestant()
    {
        $stocks = $this->rapportRepository->getStockRestant();

        $valeurTotale = $stocks->sum('valeur_ttc');

        return [
            'stocks' => $stocks,
            'valeur_totale_ttc' => round($valeurTotale, 2),
        ];
    }
}