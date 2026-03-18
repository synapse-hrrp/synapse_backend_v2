<?php

namespace Modules\Finance\App\Observers;

use Modules\Finance\App\Models\FactureOfficielle;
use Modules\Reception\App\Models\DailyRegisterEntry;

class FactureOfficielleObserver
{
    /**
     * ✅ Auto-liaison facture -> billing_request_id
     *
     * Conventions supportées:
     * A) table_source = 't_billing_requests'  && source_id = BillingRequest.id
     *    => billing_request_id = source_id
     *
     * B) table_source = 'reception_registre_journalier' && source_id = DailyRegisterEntry.id
     *    => billing_request_id = entry.billing_request_id
     */
    public function creating(FactureOfficielle $facture): void
    {
        // Déjà renseigné ? on ne touche pas
        if (!empty($facture->billing_request_id)) {
            return;
        }

        $table = (string) ($facture->table_source ?? '');
        $sourceId = (int) ($facture->source_id ?? 0);

        if ($sourceId <= 0) {
            return;
        }

        // ✅ CAS A : la facture est directement liée à BillingRequest
        if ($table === 't_billing_requests') {
            $facture->billing_request_id = $sourceId;
            return;
        }

        // ✅ CAS B : la facture est liée au registre, on récupère billing_request_id depuis l'entrée
        if ($table === 'reception_registre_journalier') {
            $entry = DailyRegisterEntry::query()->find($sourceId);
            if ($entry && !empty($entry->billing_request_id)) {
                $facture->billing_request_id = (int) $entry->billing_request_id;
            }
        }
    }

    /**
     * ✅ Quand la facture change => resync statut registre
     * (seulement si elle est liée au registre)
     */
    public function saved(FactureOfficielle $facture): void
    {
        if (($facture->table_source ?? null) !== 'reception_registre_journalier') {
            return;
        }

        $entryId = (int) ($facture->source_id ?? 0);
        if ($entryId <= 0) {
            return;
        }

        $entry = DailyRegisterEntry::query()->find($entryId);
        if (!$entry) {
            return;
        }

        $entry->syncBillingAndStatus();
    }
}