<?php

namespace Modules\Finance\App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class FinanceSourceAdapter
{
    /**
     * Lock row pour éviter double paiement concurrent
     */
    public function verrouiller(string $tableSource, int $sourceId)
    {
        $allowed = ['t_billing_requests', 'ventes'];

        if (!in_array($tableSource, $allowed, true)) {
            throw new RuntimeException("Table source non autorisée: {$tableSource}");
        }

        return DB::table($tableSource)
            ->where('id', $sourceId)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Montant total dû
     */
    public function montantDu(string $moduleSource, object $source): float
    {
        return match (strtolower($moduleSource)) {
            'reception' => (float) ($source->montant_total ?? 0),
            'pharmacie' => (float) ($source->montant_ttc ?? 0),
            default     => throw new RuntimeException("Module source non supporté: {$moduleSource}"),
        };
    }

    /**
     * Montant déjà payé (champ présent dans les tables source)
     * (NB: côté Finance, on se base plutôt sur SUM(paiements valides),
     * mais on garde cette méthode pour compat.)
     */
    public function montantPayeActuel(string $moduleSource, object $source): float
    {
        return (float) ($source->montant_paye ?? 0);
    }

    /**
     * Vérifie si la source est annulée
     */
    public function sourceEstAnnulee(string $moduleSource, object $source): bool
    {
        $statut = (string) ($source->statut ?? '');

        return match (strtolower($moduleSource)) {
            'reception' => $statut === 'cancelled',
            'pharmacie' => $statut === 'ANNULEE',
            default     => false,
        };
    }

    /**
     * Applique montant_paye + statut sur la table source
     *
     * IMPORTANT:
     * - On ne doit JAMAIS écraser une vente ANNULEE en EN_ATTENTE/PAYEE
     */
    public function appliquerEtat(
        string $moduleSource,
        string $tableSource,
        int $sourceId,
        float $nouveauMontantPaye,
        float $montantDu
    ): void {
        $moduleSource = strtolower($moduleSource);

        // ✅ Protection Pharmacie: ne jamais modifier le statut si ANNULEE
        if ($moduleSource === 'pharmacie') {
            $currentStatut = DB::table($tableSource)->where('id', $sourceId)->value('statut');

            if ($currentStatut === 'ANNULEE') {
                DB::table($tableSource)
                    ->where('id', $sourceId)
                    ->update([
                        'montant_paye' => $nouveauMontantPaye,
                        'updated_at'   => now(),
                    ]);
                return;
            }
        }

        [$statut] = $this->calculerStatutSource($moduleSource, $nouveauMontantPaye, $montantDu);

        DB::table($tableSource)
            ->where('id', $sourceId)
            ->update([
                'montant_paye' => $nouveauMontantPaye,
                'statut'       => $statut,
                'updated_at'   => now(),
            ]);
    }

    /**
     * Calcule le statut source selon le module.
     *
     * IMPORTANT:
     * - Réception: enum('pending','partial','paid','cancelled')
     * - Pharmacie (ventes): enum('EN_ATTENTE','PAYEE','ANNULEE')
     *   => PAS de PARTIELLEMENT_PAYEE (sinon erreur MySQL)
     *
     * Option A: Finance pilote le passage à PAYEE quand soldé.
     */
    public function calculerStatutSource(string $moduleSource, float $paye, float $total): array
    {
        $moduleSource = strtolower($moduleSource);

        if ($moduleSource === 'reception') {
            if ($paye <= 0) return ['pending'];
            if ($paye < $total) return ['partial'];
            return ['paid'];
        }

        if ($moduleSource === 'pharmacie') {
            // ventes.statut ENUM('EN_ATTENTE','PAYEE','ANNULEE')
            // Partiel => EN_ATTENTE, Soldé => PAYEE
            if ($total > 0 && $paye >= $total) return ['PAYEE'];
            return ['EN_ATTENTE'];
        }

        throw new RuntimeException("Module source non supporté: {$moduleSource}");
    }
}
