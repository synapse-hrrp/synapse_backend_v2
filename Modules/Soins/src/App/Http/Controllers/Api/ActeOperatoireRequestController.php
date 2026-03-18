<?php

namespace Modules\Soins\App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Soins\App\Models\ActeOperatoireRequest;
use Modules\Reception\App\Services\TariffResolverService;

class ActeOperatoireRequestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private TariffResolverService $tariffResolver
    ) {}

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', ActeOperatoireRequest::class);

        $request->validate([
            'patient_id'          => ['required', 'integer', 'exists:t_patients,id'],
            'registre_id'         => ['required', 'integer'],
            'billable_service_id' => ['required', 'integer', 'exists:billable_services,id'],
            'plan_id'             => ['required', 'integer', 'exists:tariff_plans,id'],
            'type_operation'      => ['nullable', 'string', 'max:200'],
            'indication'          => ['nullable', 'string'],
            'is_urgent'           => ['boolean'],
            'agent_id'            => ['nullable', 'integer', 'exists:t_agents,id'],
            'date_prevue'         => ['nullable', 'date'],
        ]);

        $tariffItem = $this->tariffResolver->resoudre(
            categorie: 'acte_operatoire',
            planId:    $request->plan_id,
            serviceId: $request->billable_service_id,
        );

        if (!$tariffItem) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun tarif actif trouvé pour cette combinaison.',
            ], 422);
        }

        $acteRequest = ActeOperatoireRequest::create([
            'patient_id'         => $request->patient_id,
            'registre_id'        => $request->registre_id,
            'tariff_item_id'     => $tariffItem->id,
            'unit_price_applied' => $tariffItem->prix_unitaire,
            'type_operation'     => $request->type_operation,
            'indication'         => $request->indication,
            'is_urgent'          => $request->is_urgent ?? false,
            'agent_id'           => $request->agent_id,
            'date_prevue'        => $request->date_prevue,
            'status'             => 'pending_payment',
        ]);


         // Notifier si urgent
        if ($acteRequest->is_urgent) {
            broadcast(new \Modules\Finance\App\Events\WorklistUpdated(
                module:    'soins',
                action:    'urgent',
                requestId: $acteRequest->id,
                patientId: $acteRequest->patient_id,
                isUrgent:  true,
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Demande d\'acte opératoire créée avec succès.',
            'data'    => $acteRequest,
        ], 201);
    }

    public function worklist(): JsonResponse
    {
        $this->authorize('viewWorklist', ActeOperatoireRequest::class);

        $requests = ActeOperatoireRequest::autorises()
            ->orderByDesc('is_urgent')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'data' => $requests]);
    }

    public function pending(): JsonResponse
    {
        $this->authorize('viewWorklist', ActeOperatoireRequest::class);

        $requests = ActeOperatoireRequest::enAttente()
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'data' => $requests]);
    }
}