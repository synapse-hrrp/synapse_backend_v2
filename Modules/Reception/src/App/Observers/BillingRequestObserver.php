<?php

namespace Modules\Reception\App\Observers;

use Illuminate\Support\Facades\DB;
use Modules\Reception\App\Models\BillingRequest;
use Modules\Reception\App\Models\DailyRegisterEntry;
use Modules\Finance\App\Models\FactureOfficielle;

class BillingRequestObserver
{
    public function saved(BillingRequest $br): void
    {
        // On ne gère que la réception (adapte si besoin)
        if (($br->module_source ?? null) !== 'reception') {
            return;
        }

        // Si tu veux : uniquement si total > 0
        // if ((float)($br->montant_total ?? 0) <= 0) return;

        DB::transaction(function () use ($br) {

            // Trouver l'entrée registre liée (si ref_source = id registre)
            $entry = DailyRegisterEntry::query()
                ->where('id', (int)($br->ref_source ?? 0))
                ->first();

            // On "upsert" la facture officielle via billing_request_id
            $facture = FactureOfficielle::query()
                ->where('billing_request_id', (int)$br->id)
                ->first();

            if (!$facture) {
                $facture = new FactureOfficielle();
                $facture->billing_request_id = (int)$br->id;

                // 🔥 garde ta logique actuelle si tu as déjà un générateur
                // Sinon: on met un placeholder, puis on finalise après save (id connu)
                $facture->numero_global = 'PENDING';
            }

            $facture->module_source = 'reception';

            // ✅ important : garde table_source/source_id compatibles avec ton observer existant
            // Ton FactureOfficielleObserver check: table_source === 'reception_registre_journalier'
            if ($entry) {
                $facture->table_source = 'reception_registre_journalier';
                $facture->source_id    = (int)$entry->id;
            } else {
                // fallback : facture directement liée au BR
                $facture->table_source = 't_billing_requests';
                $facture->source_id    = (int)$br->id;
            }

            $facture->client_nom = $facture->client_nom ?? ($br->patient?->personne?->nom ?? null);
            $facture->client_reference = $facture->client_reference ?? (string)($br->id_patient ?? '');
            $facture->date_emission = $facture->date_emission ?? now()->toDateString();

            // Totaux (adapte si tu calcules TVA ailleurs)
            $facture->total_ht  = (float)($br->montant_total ?? 0);
            $facture->total_tva = (float)($facture->total_tva ?? 0);
            $facture->total_ttc = (float)($br->montant_total ?? 0);

            // Statut paiement (simple)
            $paid = (float)($br->montant_paye ?? 0);
            $due  = (float)($br->montant_total ?? 0);

            if ($due > 0 && $paid >= $due) {
                $facture->statut_paiement = FactureOfficielle::PAY_PAID;
            } elseif ($paid > 0) {
                $facture->statut_paiement = FactureOfficielle::PAY_PARTIALLY;
            } else {
                $facture->statut_paiement = FactureOfficielle::PAY_UNPAID;
            }

            $facture->save();

            // Finaliser numero_global si placeholder
            if ($facture->numero_global === 'PENDING') {
                $facture->numero_global = sprintf('FAC-%s-%06d', now()->format('Y'), $facture->id);
                $facture->save();
            }
        });
    }
}