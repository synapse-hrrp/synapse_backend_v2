<?php

namespace Modules\Pharmacie\App\Interfaces;

interface RapportInterface
{
    /**
     * Rapport ventes du jour
     */
    public function ventesJour(string $date);

    /**
     * Rapport ventes de la semaine
     */
    public function ventesSemaine(string $dateDebut, string $dateFin);

    /**
     * Rapport ventes du mois
     */
    public function ventesMois(int $annee, int $mois);

    /**
     * Rapport stock restant + valeur TTC
     */
    public function stockRestant();
}