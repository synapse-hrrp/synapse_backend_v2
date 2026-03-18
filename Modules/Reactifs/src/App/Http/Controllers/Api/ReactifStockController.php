<?php

namespace Modules\Reactifs\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Reactifs\App\Models\Reactif;
use Modules\Reactifs\App\Models\ReactifStockMouvement;
use Modules\Reactifs\App\Services\ReactifStockService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReactifStockController extends Controller
{
    public function __construct(
        private ReactifStockService $stockService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $mouvements = ReactifStockMouvement::with('reactif')
            ->when($request->reactif_id, fn($q) => $q->where('reactif_id', $request->reactif_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->date_debut, fn($q) => $q->whereDate('date_mouvement', '>=', $request->date_debut))
            ->when($request->date_fin, fn($q) => $q->whereDate('date_mouvement', '<=', $request->date_fin))
            ->latest('date_mouvement')
            ->paginate(30);

        return response()->json([
            'data' => $mouvements,
        ]);
    }

    public function entree(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reactif_id' => 'required|exists:reactifs,id',
            'quantite'   => 'required|numeric|min:0.001',
            'motif'      => 'nullable|string|max:255',
        ]);

        $reactif   = Reactif::findOrFail($validated['reactif_id']);
        $mouvement = $this->stockService->entreeManuelle(
            $reactif,
            $validated['quantite'],
            $validated['motif'] ?? null
        );

        return response()->json([
            'message'   => "Entrée de {$validated['quantite']} {$reactif->unite} enregistrée.",
            'data'      => $mouvement,
            'stock_actuel' => $reactif->fresh()->stock_actuel,
        ]);
    }

    public function sortie(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reactif_id' => 'required|exists:reactifs,id',
            'quantite'   => 'required|numeric|min:0.001',
            'motif'      => 'nullable|string|max:255',
        ]);

        $reactif   = Reactif::findOrFail($validated['reactif_id']);
        $mouvement = $this->stockService->sortieManuelle(
            $reactif,
            $validated['quantite'],
            $validated['motif'] ?? null
        );

        return response()->json([
            'message'      => "Sortie de {$validated['quantite']} {$reactif->unite} enregistrée.",
            'data'         => $mouvement,
            'stock_actuel' => $reactif->fresh()->stock_actuel,
        ]);
    }

    public function ajustement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reactif_id'    => 'required|exists:reactifs,id',
            'nouveau_stock' => 'required|numeric|min:0',
            'motif'         => 'nullable|string|max:255',
        ]);

        $reactif   = Reactif::findOrFail($validated['reactif_id']);
        $mouvement = $this->stockService->ajustement(
            $reactif,
            $validated['nouveau_stock'],
            $validated['motif'] ?? null
        );

        return response()->json([
            'message'      => "Stock ajusté à {$validated['nouveau_stock']} {$reactif->unite}.",
            'data'         => $mouvement,
            'stock_actuel' => $reactif->fresh()->stock_actuel,
        ]);
    }

    public function alertes(): JsonResponse
    {
        $reactifs = $this->stockService->getReactifsEnAlerte();

        return response()->json([
            'data'  => $reactifs,
            'total' => $reactifs->count(),
        ]);
    }
}