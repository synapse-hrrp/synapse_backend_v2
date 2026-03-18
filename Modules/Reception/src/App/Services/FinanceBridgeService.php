<?php

namespace Modules\Reception\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\App\Models\FactureOfficielle;
use Modules\Finance\App\Models\FinancePayment;
use Modules\Reception\App\Models\BillingRequest;
use RuntimeException;

class FinanceBridgeService
{
    public const MODULE_SOURCE = 'reception';
    public const TABLE_SOURCE  = 't_billing_requests';

    // ✅ ENUMs DB factures_officielles
    public const FACTURE_STATUT_EMISE    = 'EMISE';
    public const FACTURE_STATUT_ANNULEE  = 'ANNULEE';
    public const FACTURE_STATUT_CLOTUREE = 'CLOTUREE';

    public const PAIEMENT_NON_PAYE = 'NON_PAYE';
    public const PAIEMENT_PARTIEL  = 'PARTIEL';
    public const PAIEMENT_PAYE     = 'PAYE';

    /**
     * Crée (ou retourne) la FactureOfficielle liée à une BillingRequest.
     * ✅ Remplit aussi billing_request_id (si colonne existe).
     */
    public function ensureFactureOfficielleForBillingRequest(BillingRequest $billing): FactureOfficielle
    {
        if (!$billing->id) {
            throw new RuntimeException("Facturation invalide: billing_request_id manquant.");
        }

        if (!$billing->id_patient) {
            throw new RuntimeException("Facturation invalide: patient manquant.");
        }

        if ((float) $billing->montant_total <= 0) {
            throw new RuntimeException("Impossible de créer une facture finance avec un total nul.");
        }

        // ✅ si existe déjà: on la retourne (et on peut resync statut paiement)
        $existing = FactureOfficielle::query()
            ->where('module_source', self::MODULE_SOURCE)
            ->where('table_source', self::TABLE_SOURCE)
            ->where('source_id', $billing->id)
            ->first();

        if ($existing) {
            // Optionnel: resync statut_paiement à chaque appel
            $existing->statut_paiement = $this->computePaymentStatus($billing);
            $existing->save();

            // Optionnel: si la colonne existe, on la force aussi
            if (array_key_exists('billing_request_id', $existing->getAttributes()) || $existing->isFillable('billing_request_id')) {
                if (empty($existing->billing_request_id)) {
                    $existing->billing_request_id = (int) $billing->id;
                    $existing->save();
                }
            }

            return $existing;
        }

        $paymentStatus = $this->computePaymentStatus($billing);

        // ✅ Création safe + numero_global unique
        return DB::transaction(function () use ($billing, $paymentStatus) {

            $data = [
                'numero_global'    => $this->generateNumeroGlobalUnique(),
                'module_source'    => self::MODULE_SOURCE,
                'table_source'     => self::TABLE_SOURCE,
                'source_id'        => (int) $billing->id,

                // Montants snapshot
                'total_ht'         => $billing->montant_total,
                'total_tva'        => 0,
                'total_ttc'        => $billing->montant_total,

                'client_nom'       => null,
                'client_reference' => (string) $billing->id_patient,
                'date_emission'    => now()->toDateString(),

                'statut'           => self::FACTURE_STATUT_EMISE,
                'statut_paiement'  => $paymentStatus,
            ];

            // ✅ si tu as bien ajouté la colonne en DB
            if ($this->factureHasColumn('billing_request_id')) {
                $data['billing_request_id'] = (int) $billing->id;
            }

            return FactureOfficielle::create($data);
        });
    }

    public function totalPaidForBillingRequest(BillingRequest $billing): float
    {
        return FinancePayment::totalPaye(self::TABLE_SOURCE, (int) $billing->id);
    }

    public function computePaymentStatus(BillingRequest $billing): string
    {
        $paid  = (float) $this->totalPaidForBillingRequest($billing);
        $total = (float) $billing->montant_total;

        if ($paid <= 0) return self::PAIEMENT_NON_PAYE;
        if ($paid + 0.00001 < $total) return self::PAIEMENT_PARTIEL;
        return self::PAIEMENT_PAYE;
    }

    /**
     * ✅ Numero global unique (anti-collision)
     * Format: FAC-2026-000001
     */
    private function generateNumeroGlobalUnique(): string
    {
        $year = now()->format('Y');

        // On boucle jusqu'à trouver un numéro libre (collision rare mais possible)
        for ($i = 0; $i < 20; $i++) {
            $candidate = 'FAC-' . $year . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);

            $exists = FactureOfficielle::query()
                ->where('numero_global', $candidate)
                ->exists();

            if (!$exists) return $candidate;
        }

        // fallback ultra safe
        return 'FAC-' . $year . '-' . now()->format('His') . '-' . random_int(100, 999);
    }

    /**
     * Petit helper: évite crash si colonne n'existe pas encore
     */
    private function factureHasColumn(string $column): bool
    {
        try {
            // Pas besoin d'import Schema global si tu veux rester light
            return \Illuminate\Support\Facades\Schema::hasColumn('factures_officielles', $column);
        } catch (\Throwable) {
            return false;
        }
    }
}