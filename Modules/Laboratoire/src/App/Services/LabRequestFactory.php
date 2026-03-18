<?php

namespace Modules\Laboratoire\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Reception\App\Models\DailyRegisterEntry;
use Modules\Reception\App\Models\BillingRequest;
use Modules\Laboratoire\App\Models\ExamenRequest;
use Modules\Laboratoire\App\Models\LabServiceMapping;

class LabRequestFactory
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

            foreach ($br->items as $item) {
                $cat = strtoupper((string) optional($item->billableService)->categorie);
                if ($cat !== 'LABO') continue;

                $map = LabServiceMapping::query()
                    ->where('billable_service_id', (int) $item->billable_service_id)
                    ->first();

                if (!$map) continue;

                $exists = ExamenRequest::query()
                    ->where('billing_request_id', (int) $br->id)
                    ->where('examen_type_id', (int) $map->examen_type_id)
                    ->where('tariff_item_id', (int) ($item->tariff_item_id ?? 0))
                    ->exists();

                if ($exists) continue;

                ExamenRequest::query()->create([
                    'patient_id'         => (int) $br->id_patient,
                    'registre_id'        => (int) $entry->id, // ✅ jamais null ici
                    'examen_type_id'     => (int) $map->examen_type_id,
                    'tariff_item_id'     => (int) ($item->tariff_item_id ?? 0),
                    'unit_price_applied' => (float) ($item->prix_unitaire ?? 0),
                    'billing_request_id' => (int) $br->id,
                    'status'             => 'pending_payment',
                    'authorized_at'      => null,
                    'notes'              => (string) ($item->notes ?? ''),
                    'is_urgent'          => (bool) ($entry->urgence ?? false),
                ]);
            }
        });
    }
}