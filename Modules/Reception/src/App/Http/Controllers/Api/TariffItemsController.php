<?php

namespace Modules\Reception\App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Reception\App\Models\TariffItem;

class TariffItemsController extends Controller
{
    use AuthorizesRequests;

    /**
     * GET /api/v1/reception/tariffs/plans/{planId}/items
     */
    public function index(int $planId): JsonResponse
    {
        $this->authorize('tariff.view');

        $items = TariffItem::query()
            ->with('service:id,code,libelle,categorie,active')
            ->where('tariff_plan_id', $planId)
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * POST /api/v1/reception/tariffs/plans/{planId}/items
     */
    public function store(Request $request, int $planId): JsonResponse
    {
        $this->authorize('tariff.manage');

        $data = $request->validate([
            'billable_service_id' => ['required', 'integer', 'exists:billable_services,id'],
            'prix_unitaire' => ['required', 'numeric', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        // éviter doublon (plan + service)
        $exists = TariffItem::query()
            ->where('tariff_plan_id', $planId)
            ->where('billable_service_id', (int) $data['billable_service_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Une ligne tarif existe déjà pour ce service dans ce plan.',
            ], 409);
        }

        $item = TariffItem::query()->create([
            'tariff_plan_id' => $planId,
            'billable_service_id' => (int) $data['billable_service_id'],
            'prix_unitaire' => $data['prix_unitaire'],
            'active' => (bool) ($data['active'] ?? true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ligne tarifaire créée',
            'data' => $item->fresh(['service:id,code,libelle,categorie,active']),
        ], 201);
    }

    /**
     * PATCH /api/v1/reception/tariffs/items/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorize('tariff.manage');

        $item = TariffItem::query()->findOrFail($id);

        $data = $request->validate([
            'prix_unitaire' => ['nullable', 'numeric', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        $item->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Ligne tarifaire mise à jour',
            'data' => $item->fresh(['service:id,code,libelle,categorie,active']),
        ]);
    }

    /**
     * DELETE /api/v1/reception/tariffs/items/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $this->authorize('tariff.manage');

        $item = TariffItem::query()->findOrFail($id);
        $item->delete(); // soft delete (TariffItem utilise SoftDeletes)

        return response()->json([
            'success' => true,
            'message' => 'Ligne tarifaire supprimée',
        ]);
    }
}