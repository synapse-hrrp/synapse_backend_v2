<?php

namespace Modules\Pharmacie\App\Services;

use Modules\Pharmacie\App\Models\Produit;
use Modules\Pharmacie\App\Models\Depot;
use Modules\Pharmacie\App\Models\ConsommationProduit;
use Modules\Pharmacie\App\Models\SeuilStock;
use Modules\Pharmacie\App\Models\Vente;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ConsommationAnalyseService
{
    /**
     * Analyser consommation d'un produit dans un dépôt
     * et mettre à jour CMH/CMM + seuils auto
     */
    public function analyserConsommation(int $produitId, int $depotId): array
    {
        // 1. Récupérer les 4 dernières semaines
        $consommations = $this->recupererConsommations4Semaines($produitId, $depotId);

        if ($consommations->isEmpty()) {
            return [
                'cmh' => 0,
                'cmm' => 0,
                'seuil_min_auto' => null,
                'seuil_max_auto' => null,
            ];
        }

        // 2. Calculer CMH (moyenne sur 4 semaines)
        $cmh = $consommations->avg('quantite_totale');

        // 3. Calculer CMM (CMH × 4.33)
        $cmm = $cmh * 4.33;

        // 4. Mettre à jour seuil_stocks si mode AUTO_CMH
        $seuil = SeuilStock::where('produit_id', $produitId)
            ->where('depot_id', $depotId)
            ->first();

        if ($seuil && $seuil->mode === 'AUTO_CMH') {
            $seuil->update([
                'cmh_actuelle' => $cmh,
                'cmm_actuelle' => $cmm,
                'seuil_min_auto' => ceil($cmh * 1), // 1 semaine
                'seuil_max_auto' => ceil($cmh * $seuil->nb_semaines_couverture),
                'derniere_analyse_at' => now(),
            ]);
        }

        return [
            'cmh' => round($cmh, 2),
            'cmm' => round($cmm, 2),
            'seuil_min_auto' => $seuil?->seuil_min_auto,
            'seuil_max_auto' => $seuil?->seuil_max_auto,
        ];
    }

    /**
     * Enregistrer consommation hebdomadaire (appelé chaque fin de semaine)
     */
    public function enregistrerConsommationSemaine(int $produitId, int $depotId): void
    {
        $annee = now()->year;
        $semaine = now()->weekOfYear;
        $mois = now()->month;

        // Calculer quantité vendue cette semaine
        $debut = now()->startOfWeek();
        $fin = now()->endOfWeek();

        $stats = DB::table('ligne_ventes')
            ->join('ventes', 'ligne_ventes.vente_id', '=', 'ventes.id')
            ->where('ligne_ventes.produit_id', $produitId)
            ->where('ventes.depot_id', $depotId)
            ->where('ventes.statut', '!=', 'ANNULEE')
            ->whereBetween('ventes.date_vente', [$debut, $fin])
            ->selectRaw('
                SUM(CASE WHEN ventes.type = "VENTE" THEN ligne_ventes.quantite ELSE 0 END) as quantite_vendue,
                SUM(CASE WHEN ventes.type = "GRATUITE" THEN ligne_ventes.quantite ELSE 0 END) as quantite_gratuite,
                COUNT(DISTINCT ventes.id) as nb_ventes
            ')
            ->first();

        $quantiteVendue = $stats->quantite_vendue ?? 0;
        $quantiteGratuite = $stats->quantite_gratuite ?? 0;
        $nbVentes = $stats->nb_ventes ?? 0;

        // Upsert consommation
        ConsommationProduit::updateOrCreate(
            [
                'produit_id' => $produitId,
                'depot_id' => $depotId,
                'annee' => $annee,
                'semaine' => $semaine,
            ],
            [
                'mois' => $mois,
                'quantite_vendue' => $quantiteVendue,
                'quantite_gratuite' => $quantiteGratuite,
                'quantite_totale' => $quantiteVendue + $quantiteGratuite,
                'nb_ventes' => $nbVentes,
            ]
        );

        // Calculer CMH sur 4 dernières semaines
        $this->calculerCMH($produitId, $depotId);
    }

    /**
     * Calculer CMH depuis les 4 dernières semaines
     */
    private function calculerCMH(int $produitId, int $depotId): void
    {
        $consommations = $this->recupererConsommations4Semaines($produitId, $depotId);

        if ($consommations->count() < 2) {
            return; // Pas assez de données
        }

        $cmh = $consommations->avg('quantite_totale');
        $cmm = $cmh * 4.33;

        // Mettre à jour chaque consommation avec CMH/CMM
        foreach ($consommations as $conso) {
            $conso->update([
                'cmh_4_semaines' => $cmh,
                'cmm' => $cmm,
            ]);
        }
    }

    /**
     * Récupérer consommations des 4 dernières semaines
     */
    private function recupererConsommations4Semaines(int $produitId, int $depotId)
    {
        return ConsommationProduit::where('produit_id', $produitId)
            ->where('depot_id', $depotId)
            ->where('annee', now()->year)
            ->where('semaine', '<=', now()->weekOfYear)
            ->orderByDesc('semaine')
            ->limit(4)
            ->get();
    }

    /**
     * Détecter surconsommation (semaine en cours > 150% CMH)
     */
    public function detecterSurconsommation(int $produitId, int $depotId): bool
    {
        $seuil = SeuilStock::where('produit_id', $produitId)
            ->where('depot_id', $depotId)
            ->first();

        if (!$seuil || !$seuil->cmh_actuelle) {
            return false;
        }

        // Calculer consommation semaine en cours
        $debut = now()->startOfWeek();
        $quantiteSemaine = DB::table('ligne_ventes')
            ->join('ventes', 'ligne_ventes.vente_id', '=', 'ventes.id')
            ->where('ligne_ventes.produit_id', $produitId)
            ->where('ventes.depot_id', $depotId)
            ->where('ventes.statut', '!=', 'ANNULEE')
            ->where('ventes.date_vente', '>=', $debut)
            ->sum('ligne_ventes.quantite');

        // Comparer avec seuil alerte (1.5 = 150%)
        return $quantiteSemaine > ($seuil->cmh_actuelle * $seuil->seuil_alerte_surconsommation);
    }

    /**
     * Analyser tous les produits avec commande auto activée
     */
    public function analyserTous(): array
    {
        $resultats = [];

        $produits = Produit::commandeAuto()->get();

        foreach ($produits as $produit) {
            $depots = Depot::actif()->get();

            foreach ($depots as $depot) {
                $resultats[] = [
                    'produit_id' => $produit->id,
                    'produit_nom' => $produit->nom,
                    'depot_id' => $depot->id,
                    'depot_code' => $depot->code,
                    'analyse' => $this->analyserConsommation($produit->id, $depot->id),
                ];
            }
        }

        return $resultats;
    }
}