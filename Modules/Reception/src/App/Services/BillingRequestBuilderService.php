<?php

namespace Modules\Reception\App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Modules\Reception\App\Models\DailyRegisterEntry;
use Modules\Reception\App\Models\BillingRequest;
use Modules\Reception\App\Models\BillingRequestItem;
use Modules\Reception\App\Models\TariffItem;

class BillingRequestBuilderService
{
    public function __construct(
        private readonly FinanceBridgeService $financeBridge,
    ) {}

    /**
     * Retourne un statut valide même si la constante n’existe pas.
     */
    private function safeStatus(string $constName, string $fallback): string
    {
        $fq = BillingRequest::class . '::' . $constName;
        return defined($fq) ? constant($fq) : $fallback;
    }

    /**
     * Génère (ou retourne) la BillingRequest liée au registre,
     * puis crée automatiquement la FactureOfficielle (numero_global).
     */
    public function generateBillingRequest(int $entryId): ?BillingRequest
    {
        return DB::transaction(function () use ($entryId) {

            /** @var DailyRegisterEntry $entry */
            $entry = DailyRegisterEntry::query()
                ->with(['items', 'tariffPlan'])
                ->lockForUpdate()
                ->findOrFail($entryId);

            if ($entry->isLocked()) {
                throw new RuntimeException("Entrée verrouillée (fermée/annulée).");
            }

            // ✅ Si paiement non obligatoire => pas de BR, pas de facture
            if (method_exists($entry, 'paymentRequired') && !$entry->paymentRequired()) {
                if (method_exists($entry, 'syncBillingAndStatus')) {
                    $entry->syncBillingAndStatus();
                }
                return null;
            }

            // ✅ Si déjà liée, on la retourne (et on s'assure que la facture officielle existe)
            if (!empty($entry->id_demande_paiement)) {
                $existing = BillingRequest::query()
                    ->with(['items', 'patient', 'requestedByAgent'])
                    ->find($entry->id_demande_paiement);

                if ($existing) {
                    // crée la facture officielle si absente
                    $this->financeBridge->ensureFactureOfficielleForBillingRequest($existing);
                    return $existing;
                }
            }

            // ✅ Statut initial: pending (non payé)
            $initialStatus = $this->safeStatus('STATUS_PENDING', 'pending');

            // ✅ crée BR
            $br = BillingRequest::query()->create([
                'id_patient'         => (int) $entry->id_patient,
                'module_source'      => FinanceBridgeService::MODULE_SOURCE, // 'reception'
                'ref_source'         => (string) $entry->id,
                'id_agent_demandeur' => (int) ($entry->id_agent_createur ?? 0),
                'statut'             => $initialStatus,
                'montant_total'      => 0,
                'montant_paye'       => 0,
            ]);

            $total = 0.0;

            // ✅ items BR depuis lignes registre (tariff_item obligatoire pour figer le prix)
            foreach ($entry->items as $line) {
                $tariffItemId = (int) ($line->tariff_item_id ?? 0);
                if ($tariffItemId <= 0) {
                    throw new RuntimeException("Ligne registre sans tariff_item_id (prix figé requis).");
                }

                $tariffItem = TariffItem::query()
                    ->whereKey($tariffItemId)
                    ->where('active', true)
                    ->first();

                if (!$tariffItem) {
                    throw new RuntimeException("Tarif introuvable/inactif (ID={$tariffItemId}).");
                }

                $qty  = (int) ($line->quantite ?? $line->qty ?? 1);
                if ($qty <= 0) $qty = 1;

                $unit = (float) ($tariffItem->prix_unitaire ?? 0);
                if ($unit < 0) $unit = 0;

                $lineTotal = round($qty * $unit, 2);
                $total += $lineTotal;

                BillingRequestItem::query()->create([
                    'id_demande_facturation' => $br->id,
                    'billable_service_id'    => (int) $tariffItem->billable_service_id,
                    'tariff_item_id'         => (int) $tariffItem->id,
                    'quantite'               => $qty,
                    'prix_unitaire'          => $unit,

                    // ✅ ta table BillingRequestItem a "notes" (pas "remarques")
                    'notes'                  => $line->remarques ?? $line->notes ?? null,
                    // total_ligne calculé par booted()
                ]);
            }

            $br->update(['montant_total' => round($total, 2)]);

            // ✅ lier registre -> BR
            $entry->update(['id_demande_paiement' => $br->id]);

            // ✅ créer automatiquement la facture officielle (numero_global)
            // (si total = 0, FinanceBridgeService peut throw => normal)
            $this->financeBridge->ensureFactureOfficielleForBillingRequest($br);

            // ✅ sync statuts (registre / BR / facture)
            if (method_exists($entry, 'syncBillingAndStatus')) {
                $entry->syncBillingAndStatus();
            }

            return $br->fresh(['items', 'patient', 'requestedByAgent']);
        });
    }
}