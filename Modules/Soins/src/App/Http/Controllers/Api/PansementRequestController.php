<?php

namespace Modules\Soins\App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Soins\App\Models\PansementRequest;
use Modules\Reception\App\Services\TariffResolverService;

class PansementRequestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private TariffResolverService $tariffResolver
    ) {}

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', PansementRequest::class);

        $validated = $request->validate([
            'patient_id'          => ['required', 'integer', 'exists:t_patients,id'],
            'registre_id'         => ['required', 'integer'],
            'billable_service_id' => ['required', 'integer', 'exists:billable_services,id'],
            'plan_id'             => ['required', 'integer', 'exists:tariff_plans,id'],
            'billing_request_id'  => ['nullable', 'integer', 'exists:t_billing_requests,id'],
            'type_pansement'      => ['required', 'in:simple,compressif,chirurgical,occlusif,humide'],
            'zone_anatomique'     => ['nullable', 'string', 'max:150'],
            'notes'               => ['nullable', 'string'],
            'is_urgent'           => ['boolean'],
            'agent_id'            => ['nullable', 'integer', 'exists:t_agents,id'],
        ]);

        $tariffItem = $this->tariffResolver->resoudre(
            categorie: 'pansement',
            planId:    (int) $validated['plan_id'],
            serviceId: (int) $validated['billable_service_id'],
        );

        if (!$tariffItem) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun tarif actif trouvé pour cette combinaison.',
            ], 422);
        }

        $pansementRequest = PansementRequest::create([
            'patient_id'         => (int) $validated['patient_id'],
            'registre_id'        => (int) $validated['registre_id'],
            'tariff_item_id'     => (int) $tariffItem->id,
            'unit_price_applied' => $tariffItem->prix_unitaire,
            'billing_request_id' => $validated['billing_request_id'] ?? null,
            'type_pansement'     => (string) $validated['type_pansement'],
            'zone_anatomique'    => $validated['zone_anatomique'] ?? null,
            'notes'              => $validated['notes'] ?? null,
            'is_urgent'          => (bool) ($validated['is_urgent'] ?? false),
            'agent_id'           => $validated['agent_id'] ?? null,
            'status'             => 'pending_payment',
            'authorized_at'      => null,
            'completed_at'       => null,
        ]);

        if ($pansementRequest->is_urgent) {
            broadcast(new \Modules\Finance\App\Events\WorklistUpdated(
                module:    'soins',
                action:    'urgent',
                requestId: $pansementRequest->id,
                patientId: $pansementRequest->patient_id,
                isUrgent:  true,
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Demande de pansement créée avec succès.',
            'data'    => $pansementRequest,
        ], 201);
    }

    public function worklist(): JsonResponse
    {
        $this->authorize('viewWorklist', PansementRequest::class);

        $requests = PansementRequest::autorises()
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
        $this->authorize('viewWorklist', PansementRequest::class);

        $requests = PansementRequest::enAttente()
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $requests,
        ]);
    }
}