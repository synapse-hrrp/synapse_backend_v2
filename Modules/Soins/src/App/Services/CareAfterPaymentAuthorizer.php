<?php

namespace Modules\Soins\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Reception\App\Models\BillingRequest;

use Modules\Soins\App\Models\ConsultationRequest;
use Modules\Soins\App\Models\HospitalisationRequest;
use Modules\Soins\App\Models\AccouchementRequest;
use Modules\Soins\App\Models\ActeOperatoireRequest;
use Modules\Soins\App\Models\PansementRequest;
use Modules\Soins\App\Models\KinesitherapieRequest;

class CareAfterPaymentAuthorizer
{
    public function authorizeForBillingRequest(int $billingRequestId): void
    {
        DB::transaction(function () use ($billingRequestId) {

            $br = BillingRequest::query()->lockForUpdate()->find($billingRequestId);
            if (!$br) return;

            if (($br->statut ?? null) !== BillingRequest::STATUS_PAID) return;

            $payload = [
                'status'        => 'authorized',
                'authorized_at' => now(),
            ];

            $this->bulkUpdate($billingRequestId, 'pending_payment', $payload);
        });
    }

    public function deauthorizeForBillingRequest(int $billingRequestId): void
    {
        DB::transaction(function () use ($billingRequestId) {

            $br = BillingRequest::query()->lockForUpdate()->find($billingRequestId);
            if (!$br) return;

            if (($br->statut ?? null) === BillingRequest::STATUS_PAID) return;

            $payload = [
                'status'        => 'pending_payment',
                'authorized_at' => null,
            ];

            $this->bulkUpdate($billingRequestId, 'authorized', $payload);
        });
    }

    private function bulkUpdate(int $billingRequestId, string $fromStatus, array $payload): void
    {
        ConsultationRequest::query()
            ->where('billing_request_id', $billingRequestId)
            ->where('status', $fromStatus)
            ->update($payload);

        HospitalisationRequest::query()
            ->where('billing_request_id', $billingRequestId)
            ->where('status', $fromStatus)
            ->update($payload);

        AccouchementRequest::query()
            ->where('billing_request_id', $billingRequestId)
            ->where('status', $fromStatus)
            ->update($payload);

        ActeOperatoireRequest::query()
            ->where('billing_request_id', $billingRequestId)
            ->where('status', $fromStatus)
            ->update($payload);

        PansementRequest::query()
            ->where('billing_request_id', $billingRequestId)
            ->where('status', $fromStatus)
            ->update($payload);

        KinesitherapieRequest::query()
            ->where('billing_request_id', $billingRequestId)
            ->where('status', $fromStatus)
            ->update($payload);
    }
}