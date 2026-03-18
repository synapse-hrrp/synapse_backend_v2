<?php

namespace Modules\Imagerie\App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Imagerie\App\Models\ImagerieRequest;
use Modules\Reception\App\Services\TariffResolverService;

class ImagerieRequestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private TariffResolverService $tariffResolver
    ) {}

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', ImagerieRequest::class);

        $request->validate([
            'patient_id'               => ['required', 'integer', 'exists:t_patients,id'],
            'registre_id'              => ['required', 'integer'],
            'billable_service_id'      => ['required', 'integer', 'exists:billable_services,id'],
            'plan_id'                  => ['required', 'integer', 'exists:tariff_plans,id'],
            'imagerie_type_id'         => ['required', 'integer', 'exists:imagerie_types,id'],
            'region_anatomique'        => ['nullable', 'string', 'max:100'],
            'renseignements_cliniques' => ['nullable', 'string'],
            'is_urgent'                => ['boolean'],
            'agent_id'                 => ['nullable', 'integer', 'exists:t_agents,id'],
        ]);

        $tariffItem = $this->tariffResolver->resoudre(
            categorie: 'imagerie',
            planId:    $request->plan_id,
            serviceId: $request->billable_service_id,
        );

        if (!$tariffItem) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun tarif actif trouvé pour cette combinaison.',
            ], 422);
        }

        $imagerieRequest = ImagerieRequest::create([
            'patient_id'               => $request->patient_id,
            'registre_id'              => $request->registre_id,
            'imagerie_type_id'         => $request->imagerie_type_id,
            'tariff_item_id'           => $tariffItem->id,
            'unit_price_applied'       => $tariffItem->prix_unitaire,
            'region_anatomique'        => $request->region_anatomique,
            'renseignements_cliniques' => $request->renseignements_cliniques,
            'is_urgent'                => $request->is_urgent ?? false,
            'agent_id'                 => $request->agent_id,
            'status'                   => 'pending_payment',
        ]);

        // Notifier si urgent
        if ($imagerieRequest->is_urgent) {
            broadcast(new \Modules\Finance\App\Events\WorklistUpdated(
                module:    'imagerie',
                action:    'urgent',
                requestId: $imagerieRequest->id,
                patientId: $imagerieRequest->patient_id,
                isUrgent:  true,
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Demande d\'imagerie créée avec succès.',
            'data'    => $imagerieRequest,
        ], 201);
    }

    public function worklist(): JsonResponse
    {
        $this->authorize('viewWorklist', ImagerieRequest::class);

        $requests = ImagerieRequest::with(['imagerieType'])
            ->autorises()
            ->orderByDesc('is_urgent')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'data' => $requests]);
    }

    public function pending(): JsonResponse
    {
        $this->authorize('viewWorklist', ImagerieRequest::class);

        $requests = ImagerieRequest::with(['imagerieType'])
            ->enAttente()
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'data' => $requests]);
    }
}