<?php

namespace Modules\Reception\App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Reception\App\Services\TariffResolverService;

class TariffController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly TariffResolverService $tariffs
    ) {}

    /**
     * GET /api/v1/reception/tariffs/plans
     * Plans actifs (pour le front)
     */
    public function plans(): JsonResponse
    {
        $this->authorize('tariff.view');

        return response()->json([
            'success' => true,
            'data' => $this->tariffs->listPlans(),
        ]);
    }

    /**
     * GET /api/v1/reception/tariffs/services?plan_id=1&categorie=consultation
     * GET /api/v1/reception/tariffs/services?tariff_plan_id=1&categorie=consultation
     *
     * - categorie optionnelle
     * - si categorie absente => toutes les prestations du plan
     */
    public function services(Request $request): JsonResponse
    {
        $this->authorize('tariff.view');

        $data = $request->validate([
            'plan_id'        => ['nullable', 'integer', 'exists:tariff_plans,id'],
            'tariff_plan_id' => ['nullable', 'integer', 'exists:tariff_plans,id'],
            'categorie'      => ['nullable', 'string'],
        ]);

        $planId = (int) ($data['tariff_plan_id'] ?? $data['plan_id'] ?? 0);

        if ($planId <= 0) {
            return response()->json([
                'success' => false,
                'message' => "plan_id (ou tariff_plan_id) est requis",
            ], 422);
        }

        $categorie = $data['categorie'] ?? null;

        if (!empty($categorie)) {
            return response()->json([
                'success' => true,
                'data' => $this->tariffs->listServicesByCategoryAndPlan($categorie, $planId),
            ]);
        }

        // toutes les prestations du plan
        return response()->json([
            'success' => true,
            'data' => $this->tariffs->listServicesByPlan($planId),
        ]);
    }
}