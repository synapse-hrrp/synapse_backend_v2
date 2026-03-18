<?php

namespace Modules\Finance\App\Observers;

use Modules\Finance\App\Models\FinancePayment;
use Modules\Reception\App\Models\DailyRegisterEntry;

// ✅ Labo
use Modules\Laboratoire\App\Services\LabAfterPaymentAuthorizer;

// ✅ Soins
use Modules\Soins\App\Services\CareAfterPaymentAuthorizer;

// ✅ Imagerie (AJOUT)
use Modules\Imagerie\App\Services\ImagerieAfterPaymentAuthorizer;

class FinancePaymentObserver
{
    public function created(FinancePayment $payment): void
    {
        $this->syncReception($payment);
    }

    public function updated(FinancePayment $payment): void
    {
        $this->syncReception($payment);
    }

    private function syncReception(FinancePayment $payment): void
    {
        if (($payment->module_source ?? null) !== 'reception') return;
        if (($payment->table_source ?? null) !== 't_billing_requests') return;

        $billingRequestId = (int) ($payment->source_id ?? 0);
        if ($billingRequestId <= 0) return;

        $entry = DailyRegisterEntry::query()
            ->where('id_demande_paiement', $billingRequestId)
            ->first();

        if (!$entry) return;

        // 1) synchro BR + facture + registre
        $entry->syncBillingAndStatus();

        $isValid = $payment->estValide();
        $isVoid  = $payment->estAnnule();

        // 2) LABO
        $lab = app(LabAfterPaymentAuthorizer::class);
        if ($isValid) $lab->authorizeForBillingRequest($billingRequestId);
        if ($isVoid)  $lab->deauthorizeForBillingRequest($billingRequestId);

        // 3) SOINS
        $care = app(CareAfterPaymentAuthorizer::class);
        if ($isValid) $care->authorizeForBillingRequest($billingRequestId);
        if ($isVoid)  $care->deauthorizeForBillingRequest($billingRequestId);

        // 4) IMAGERIE (AJOUT)
        $img = app(ImagerieAfterPaymentAuthorizer::class);
        if ($isValid) $img->authorizeForBillingRequest($billingRequestId);
        if ($isVoid)  $img->deauthorizeForBillingRequest($billingRequestId);
    }
}