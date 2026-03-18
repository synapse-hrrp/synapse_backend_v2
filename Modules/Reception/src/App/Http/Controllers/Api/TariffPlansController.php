<?php

namespace Modules\Reception\App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Reception\App\Models\TariffPlan;

class TariffPlansController extends Controller
{
    use AuthorizesRequests;

    /**
     * GET /api/v1/reception/tariffs/plans/{id}
     */
    public function show(int $id): JsonResponse
    {
        $this->authorize('tariff.view');

        $plan = TariffPlan::query()->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $plan,
        ]);
    }

    /**
     * POST /api/v1/reception/tariffs/plans
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('tariff.manage');

        $data = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
            'paiement_obligatoire' => ['nullable', 'boolean'],
        ]);

        $plan = TariffPlan::query()->create([
            'nom' => $data['nom'],
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'active' => (bool) ($data['active'] ?? true),
            'paiement_obligatoire' => (bool) ($data['paiement_obligatoire'] ?? false),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plan tarifaire créé',
            'data' => $plan,
        ], 201);
    }

    /**
     * PATCH /api/v1/reception/tariffs/plans/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorize('tariff.manage');

        $plan = TariffPlan::query()->findOrFail($id);

        $data = $request->validate([
            'nom' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
            'paiement_obligatoire' => ['nullable', 'boolean'],
        ]);

        $plan->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Plan tarifaire mis à jour',
            'data' => $plan->fresh(),
        ]);
    }

    /**
     * DELETE /api/v1/reception/tariffs/plans/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $this->authorize('tariff.manage');

        $plan = TariffPlan::query()->findOrFail($id);

        // soft delete si TariffPlan utilise SoftDeletes
        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plan tarifaire supprimé',
        ]);
    }
}