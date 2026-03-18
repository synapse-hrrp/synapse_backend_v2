<?php

namespace Modules\Imagerie\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Reception\App\Models\BillingRequest;
use Modules\Imagerie\App\Models\ImagerieRequest;

class ImagerieAfterPaymentAuthorizer
{
    public function authorizeForBillingRequest(int $billingRequestId): void
    {
        DB::transaction(function () use ($billingRequestId) {

            $br = BillingRequest::query()->lockForUpdate()->find($billingRequestId);
            if (!$br) return;

            if (($br->statut ?? null) !== BillingRequest::STATUS_PAID) return;

            ImagerieRequest::query()
                ->where('billing_request_id', $billingRequestId)
                ->where('status', 'pending_payment')
                ->update([
                    'status'        => 'authorized',
                    'authorized_at' => now(),
                ]);
        });
    }

    public function deauthorizeForBillingRequest(int $billingRequestId): void
    {
        DB::transaction(function () use ($billingRequestId) {

            $br = BillingRequest::query()->lockForUpdate()->find($billingRequestId);
            if (!$br) return;

            if (($br->statut ?? null) === BillingRequest::STATUS_PAID) return;

            ImagerieRequest::query()
                ->where('billing_request_id', $billingRequestId)
                ->where('status', 'authorized')
                ->update([
                    'status'        => 'pending_payment',
                    'authorized_at' => null,
                ]);
        });
    }
}