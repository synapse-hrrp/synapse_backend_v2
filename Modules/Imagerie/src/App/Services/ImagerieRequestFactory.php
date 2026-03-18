<?php

namespace Modules\Imagerie\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Reception\App\Models\DailyRegisterEntry;
use Modules\Reception\App\Models\BillingRequest;

use Modules\Imagerie\App\Models\ImagerieRequest;
use Modules\Imagerie\App\Models\ImagerieServiceMapping;

class ImagerieRequestFactory
{
    public function createPendingForEntry(DailyRegisterEntry $entry): void
    {
        $billingRequestId = (int) ($entry->id_demande_paiement ?? 0);
        if ($billingRequestId <= 0) return;

        DB::transaction(function () use ($entry, $billingRequestId) {

            $br = BillingRequest::query()
                ->with(['items.billableService'])
                ->lockForUpdate()
                ->find($billingRequestId);

            if (!$br) return;

            $patientId = (int) ($br->id_patient ?? 0);
            if ($patientId <= 0) return;

            $isUrgent = (bool) ($entry->urgence ?? false);

            foreach ($br->items as $item) {
                $serviceId = (int) ($item->billable_service_id ?? 0);
                if ($serviceId <= 0) continue;

                // mapping service -> imagerie_type
                $map = ImagerieServiceMapping::query()
                    ->where('billable_service_id', $serviceId)
                    ->first();

                if (!$map) continue;

                $tariffItemId = (int) ($item->tariff_item_id ?? 0);

                // ✅ Anti-doublon (idempotent)
                $exists = ImagerieRequest::query()
                    ->where('billing_request_id', $billingRequestId)
                    ->where('imagerie_type_id', (int) $map->imagerie_type_id)
                    ->where('tariff_item_id', $tariffItemId)
                    ->exists();

                if ($exists) continue;

                ImagerieRequest::query()->create([
                    'patient_id'         => $patientId,
                    'registre_id'        => (int) $entry->id,

                    'imagerie_type_id'   => (int) $map->imagerie_type_id,
                    'tariff_item_id'     => $tariffItemId,
                    'unit_price_applied' => (float) ($item->prix_unitaire ?? 0),

                    'billing_request_id' => $billingRequestId,

                    'status'             => 'pending_payment',
                    'authorized_at'      => null,
                    'completed_at'       => null,

                    'region_anatomique'        => null,
                    'renseignements_cliniques' => (string) ($item->notes ?? ''),

                    'is_urgent'          => $isUrgent,
                    'agent_id'           => null,
                ]);
            }
        });
    }
}