<?php

namespace Modules\Soins\App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Soins\App\Models\KinesitherapieRequest;
use Modules\Reception\App\Services\TariffResolverService;

class KinesitherapieRequestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private TariffResolverService $tariffResolver
    ) {}

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', KinesitherapieRequest::class);

        $validated = $request->validate([
            'patient_id'          => ['required', 'integer', 'exists:t_patients,id'],
            'registre_id'         => ['required', 'integer'],
            'billable_service_id' => ['required', 'integer', 'exists:billable_services,id'],
            'plan_id'             => ['required', 'integer', 'exists:tariff_plans,id'],
            'billing_request_id'  => ['nullable', 'integer', 'exists:t_billing_requests,id'],
            'type_reeducation'    => ['required', 'in:motrice,respiratoire,post_operatoire,neurologique,pediatrique,sportive'],
            'motif'               => ['nullable', 'string'],
            'notes'               => ['nullable', 'string'],
            'is_urgent'           => ['boolean'],
            'agent_id'            => ['nullable', 'integer', 'exists:t_agents,id'],
        ]);

        $tariffItem = $this->tariffResolver->resoudre(
            categorie: 'kinesitherapie',
            planId:    (int) $validated['plan_id'],
            serviceId: (int) $validated['billable_service_id'],
        );

        if (!$tariffItem) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun tarif actif trouvé pour cette combinaison.',
            ], 422);
        }

        $kineRequest = KinesitherapieRequest::create([
            'patient_id'         => (int) $validated['patient_id'],
            'registre_id'        => (int) $validated['registre_id'],
            'tariff_item_id'     => (int) $tariffItem->id,
            'unit_price_applied' => (float) $tariffItem->prix_unitaire,
            'billing_request_id' => $validated['billing_request_id'] ?? null,
            'type_reeducation'   => (string) $validated['type_reeducation'],
            'motif'              => $validated['motif'] ?? null,
            'notes'              => $validated['notes'] ?? null,
            'is_urgent'          => (bool) ($validated['is_urgent'] ?? false),
            'agent_id'           => $validated['agent_id'] ?? null,
            'status'             => 'pending_payment',
            'authorized_at'      => null,
            'completed_at'       => null,
        ]);

        if ($kineRequest->is_urgent) {
            broadcast(new \Modules\Finance\App\Events\WorklistUpdated(
                module:    'soins',
                action:    'urgent',
                requestId: $kineRequest->id,
                patientId: $kineRequest->patient_id,
                isUrgent:  true,
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Demande de kinésithérapie créée avec succès.',
            'data'    => $kineRequest,
        ], 201);
    }

    public function worklist(): JsonResponse
    {
        $this->authorize('viewWorklist', KinesitherapieRequest::class);

        $requests = KinesitherapieRequest::autorises()
            ->orderByDesc('is_urgent')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $requests,
        ]);
    }

    public function pending(): JsonResponse
    {
        $this->authorize('viewWorklist', KinesitherapieRequest::class);

        $requests = KinesitherapieRequest::enAttente()
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $requests,
        ]);
    }
}