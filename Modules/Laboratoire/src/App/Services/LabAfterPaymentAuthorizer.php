<?php

namespace Modules\Laboratoire\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Reception\App\Models\BillingRequest;
use Modules\Laboratoire\App\Models\ExamenRequest;

class LabAfterPaymentAuthorizer
{
    public function authorizeForBillingRequest(int $billingRequestId): void
    {
        DB::transaction(function () use ($billingRequestId) {

            $br = BillingRequest::query()
                ->lockForUpdate()
                ->find($billingRequestId);

            if (!$br) return;

            if (($br->statut ?? null) !== BillingRequest::STATUS_PAID) {
                return;
            }

            ExamenRequest::query()
                ->where('billing_request_id', (int) $br->id)
                ->where('status', 'pending_payment')
                ->update([
                    'status'        => 'authorized',
                    'authorized_at' => now(),
                ]);
        });
    }

    // ✅ NOUVEAU : annulation / paiement insuffisant => repasser en attente
    public function deauthorizeForBillingRequest(int $billingRequestId): void
    {
        DB::transaction(function () use ($billingRequestId) {

            $br = BillingRequest::query()
                ->lockForUpdate()
                ->find($billingRequestId);

            if (!$br) return;

            // Si encore payé, on ne touche pas
            if (($br->statut ?? null) === BillingRequest::STATUS_PAID) {
                return;
            }

            ExamenRequest::query()
                ->where('billing_request_id', (int) $br->id)
                ->where('status', 'authorized')
                ->update([
                    'status'        => 'pending_payment',
                    'authorized_at' => null,
                ]);
        });
    }
}