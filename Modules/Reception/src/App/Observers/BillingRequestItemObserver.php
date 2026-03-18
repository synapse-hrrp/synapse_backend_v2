<?php

namespace Modules\Reception\App\Observers;

use Modules\Reception\App\Models\BillingRequestItem;
use Modules\Reception\App\Models\DailyRegisterEntry;
use Modules\Laboratoire\App\Models\ExamenRequest;
use Modules\Laboratoire\App\Models\LabServiceMapping;

class BillingRequestItemObserver
{
    public function created(BillingRequestItem $item): void
    {
        // Charger relations nécessaires
        $item->loadMissing(['billingRequest', 'billableService']);

        $br = $item->billingRequest;
        if (!$br) return;

        // ✅ seulement services LABO (chez toi categorie = "LABO")
        $cat = strtoupper((string) optional($item->billableService)->categorie);
        if ($cat !== 'LABO') return;

        // mapping service -> examen_type
        $map = LabServiceMapping::query()
            ->where('billable_service_id', (int) $item->billable_service_id)
            ->first();

        if (!$map) return;

        // entrée registre liée à cette BR
        $entry = DailyRegisterEntry::query()
            ->where('id_demande_paiement', (int) $br->id)
            ->first();

        // ✅ CORRECTION : si registre pas encore lié, on ne crée pas maintenant
        if (!$entry) {
            return;
        }

        // ✅ idempotent : éviter doublons
        $exists = ExamenRequest::query()
            ->where('billing_request_id', (int) $br->id)
            ->where('examen_type_id', (int) $map->examen_type_id)
            ->where('tariff_item_id', (int) ($item->tariff_item_id ?? 0))
            ->exists();

        if ($exists) return;

        // créer la demande labo en attente de paiement
        ExamenRequest::query()->create([
            'patient_id'         => (int) $br->id_patient,
            'registre_id'        => (int) $entry->id, // ✅ maintenant jamais null
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
}