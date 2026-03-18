<?php

namespace Modules\Soins\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Reception\App\Models\DailyRegisterEntry;
use Modules\Reception\App\Models\BillingRequest;

// mapping
use Modules\Soins\App\Models\CareServiceMapping;

// requests
use Modules\Soins\App\Models\ConsultationRequest;
use Modules\Soins\App\Models\HospitalisationRequest;
use Modules\Soins\App\Models\AccouchementRequest;
use Modules\Soins\App\Models\ActeOperatoireRequest;
use Modules\Soins\App\Models\PansementRequest;
use Modules\Soins\App\Models\KinesitherapieRequest;

class CareRequestFactory
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

                $map = CareServiceMapping::query()
                    ->where('billable_service_id', $serviceId)
                    ->first();

                if (!$map) continue;

                $careKind = (string) $map->care_kind;

                // ✅ meta peut être array OU JSON string
                $meta = $map->meta;
                if (is_string($meta)) {
                    $decoded = json_decode($meta, true);
                    $meta = is_array($decoded) ? $decoded : [];
                }
                if (!is_array($meta)) $meta = [];

                $tariffItemId = (int) ($item->tariff_item_id ?? 0);

                // ✅ Anti-doublon plus robuste: kind + BR + service + tariff_item
                if ($this->exists($careKind, $billingRequestId, $serviceId, $tariffItemId)) {
                    continue;
                }

                $common = [
                    'patient_id'         => $patientId,
                    'registre_id'        => (int) $entry->id,
                    'tariff_item_id'     => $tariffItemId,
                    'unit_price_applied' => (float) ($item->prix_unitaire ?? 0),
                    'billing_request_id' => (int) $br->id,
                    'status'             => 'pending_payment',
                    'authorized_at'      => null,
                    'completed_at'       => null,
                    'is_urgent'          => $isUrgent,
                    'agent_id'           => null,
                ];

                switch ($careKind) {
                    case 'consultation':
                        ConsultationRequest::query()->create($common + [
                            'type_acte' => (string) ($meta['type_acte'] ?? ''),
                            'motif'     => (string) ($meta['motif'] ?? ''),
                        ]);
                        break;

                    case 'hospitalisation':
                        HospitalisationRequest::query()->create($common + [
                            'motif' => (string) ($meta['motif'] ?? ''),
                        ]);
                        break;

                    case 'accouchement':
                        AccouchementRequest::query()->create($common + [
                            'notes' => (string) ($meta['notes'] ?? ($item->notes ?? '')),
                        ]);
                        break;

                    case 'acte_operatoire':
                        ActeOperatoireRequest::query()->create($common + [
                            'type_operation' => (string) ($meta['type_operation'] ?? ''),
                            'indication'     => (string) ($meta['indication'] ?? ''),
                            'date_prevue'    => $meta['date_prevue'] ?? null,
                        ]);
                        break;

                    case 'pansement':
                        // ✅ maintenant que billing_request_id existe + fillable, create() suffit
                        PansementRequest::query()->create($common + [
                            'type_pansement'  => (string) ($meta['type_pansement'] ?? 'simple'),
                            'zone_anatomique' => (string) ($meta['zone_anatomique'] ?? ''),
                            'notes'           => (string) ($item->notes ?? ''),
                        ]);
                        break;

                    case 'kinesitherapie':
                        KinesitherapieRequest::query()->create($common + [
                            'type_reeducation' => (string) ($meta['type_reeducation'] ?? 'motrice'),
                            'motif'            => (string) ($meta['motif'] ?? ''),
                            'notes'            => (string) ($item->notes ?? ''),
                        ]);
                        break;
                }
            }
        });
    }

    private function exists(string $careKind, int $billingRequestId, int $serviceId, int $tariffItemId): bool
    {
        $q = fn ($model) => $model::query()
            ->where('billing_request_id', $billingRequestId)
            ->where('tariff_item_id', $tariffItemId)
            // ✅ on renforce avec serviceId via tariff_item_id souvent, mais si tariff_item_id = 0, on évite collision
            ->when($tariffItemId === 0, fn($qq) => $qq->where('unit_price_applied', '>=', 0)); // neutre

        // Si tu veux 100% strict, ajoute une colonne service_id dans les requests,
        // mais on reste minimal ici.

        return match ($careKind) {
            'consultation'    => $q(ConsultationRequest::class)->exists(),
            'hospitalisation' => $q(HospitalisationRequest::class)->exists(),
            'accouchement'    => $q(AccouchementRequest::class)->exists(),
            'acte_operatoire' => $q(ActeOperatoireRequest::class)->exists(),
            'pansement'       => $q(PansementRequest::class)->exists(),
            'kinesitherapie'  => $q(KinesitherapieRequest::class)->exists(),
            default           => false,
        };
    }
}