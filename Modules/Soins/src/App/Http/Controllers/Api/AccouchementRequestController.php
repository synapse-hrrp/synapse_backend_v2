<?php

namespace Modules\Soins\App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Soins\App\Models\AccouchementRequest;
use Modules\Reception\App\Services\TariffResolverService;

class AccouchementRequestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private TariffResolverService $tariffResolver
    ) {}

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', AccouchementRequest::class);

        $request->validate([
            'patient_id'          => ['required', 'integer', 'exists:t_patients,id'],
            'registre_id'         => ['required', 'integer'],
            'billable_service_id' => ['required', 'integer', 'exists:billable_services,id'],
            'plan_id'             => ['required', 'integer', 'exists:tariff_plans,id'],
            'notes'               => ['nullable', 'string'],
            'is_urgent'           => ['boolean'],
            'agent_id'            => ['nullable', 'integer', 'exists:t_agents,id'],
        ]);

        $tariffItem = $this->tariffResolver->resoudre(
            categorie: 'accouchement',
            planId:    $request->plan_id,
            serviceId: $request->billable_service_id,
        );

        if (!$tariffItem) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun tarif actif trouvé pour cette combinaison.',
            ], 422);
        }

        $accouchementRequest = AccouchementRequest::create([
            'patient_id'         => $request->patient_id,
            'registre_id'        => $request->registre_id,
            'tariff_item_id'     => $tariffItem->id,
            'unit_price_applied' => $tariffItem->prix_unitaire,
            'notes'              => $request->notes,
            'is_urgent'          => $request->is_urgent ?? false,
            'agent_id'           => $request->agent_id,
            'status'             => 'pending_payment',
        ]);

        // Notifier si urgent
        if ($accouchementRequest->is_urgent) {
            broadcast(new \Modules\Finance\App\Events\WorklistUpdated(
                module:    'soins',
                action:    'urgent',
                requestId: $accouchementRequest->id,
                patientId: $accouchementRequest->patient_id,
                isUrgent:  true,
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Demande d\'accouchement créée avec succès.',
            'data'    => $accouchementRequest,
        ], 201);
    }

    public function worklist(): JsonResponse
    {
        $this->authorize('viewWorklist', AccouchementRequest::class);

        $requests = AccouchementRequest::autorises()
            ->orderByDesc('is_urgent')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'data' => $requests]);
    }

    public function pending(): JsonResponse
    {
        $this->authorize('viewWorklist', AccouchementRequest::class);

        $requests = AccouchementRequest::enAttente()
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'data' => $requests]);
    }
}