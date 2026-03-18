<?php

namespace Modules\Soins\App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Soins\App\Models\ConsultationRequest;
use Modules\Reception\App\Services\TariffResolverService;
use Modules\Reception\App\Models\BillableService;

class ConsultationRequestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private TariffResolverService $tariffResolver
    ) {}

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', ConsultationRequest::class);

        $validated = $request->validate([
            'patient_id'          => ['required', 'integer', 'exists:t_patients,id'],
            'registre_id'         => ['required', 'integer'],
            'billable_service_id' => ['required', 'integer', 'exists:billable_services,id'],
            'plan_id'             => ['required', 'integer', 'exists:tariff_plans,id'],

            // ✅ type_acte = information métier, pas "categorie tarif"
            // on autorise tes types
            'type_acte'           => [
                'required',
                'string',
                'in:consultation,consultation_medecin_generaliste,consultation_sage_femme,consultation_specialiste,consultation_specialiste_mentor'
            ],

            'motif'               => ['nullable', 'string'],
            'is_urgent'           => ['boolean'],
            'agent_id'            => ['nullable', 'integer', 'exists:t_agents,id'],
        ]);

        $service = BillableService::query()->findOrFail((int) $validated['billable_service_id']);

        // ✅ 1) Résolution tarif par catégorie (ex: "CONSULTATION"/"consultation")
        $tariffItem = $this->tariffResolver->tryResolveTariffItemByCategory(
            planId: (int) $validated['plan_id'],
            categorie: (string) $service->categorie
        );

        // ✅ 2) fallback : si jamais tu veux prioriser le service exact quand il existe
        // (utile si tu ajoutes des tarifs spécifiques par service)
        if (!$tariffItem) {
            $tariffItem = $this->tariffResolver->tryResolveTariffItem(
                planId: (int) $validated['plan_id'],
                billableServiceId: (int) $validated['billable_service_id']
            );
        }

        if (!$tariffItem) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun tarif actif trouvé (catégorie ou service) pour ce plan.',
            ], 422);
        }

        $consultRequest = ConsultationRequest::create([
            'patient_id'         => (int) $validated['patient_id'],
            'registre_id'        => (int) $validated['registre_id'],
            'tariff_item_id'     => (int) $tariffItem->id,
            'unit_price_applied' => (float) $tariffItem->prix_unitaire,

            'billing_request_id' => $request->input('billing_request_id'), // ✅ si tu l’envoies depuis réception (optionnel)
            'type_acte'          => (string) $validated['type_acte'],
            'motif'              => (string) ($validated['motif'] ?? ''),
            'is_urgent'          => (bool) ($validated['is_urgent'] ?? false),
            'agent_id'           => $validated['agent_id'] ?? null,

            'status'             => 'pending_payment',
            'authorized_at'      => null,
            'completed_at'       => null,
        ]);

        // Notifier si urgent
        if ($consultRequest->is_urgent) {
            broadcast(new \Modules\Finance\App\Events\WorklistUpdated(
                module:    'soins',
                action:    'urgent',
                requestId: $consultRequest->id,
                patientId: $consultRequest->patient_id,
                isUrgent:  true,
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Demande de consultation créée avec succès.',
            'data'    => $consultRequest,
        ], 201);
    }

    public function worklist(): JsonResponse
    {
        $this->authorize('viewWorklist', ConsultationRequest::class);

        $requests = ConsultationRequest::autorises()
            ->orderByDesc('is_urgent')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'data' => $requests]);
    }

    public function pending(): JsonResponse
    {
        $this->authorize('viewWorklist', ConsultationRequest::class);

        $requests = ConsultationRequest::enAttente()
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'data' => $requests]);
    }
}