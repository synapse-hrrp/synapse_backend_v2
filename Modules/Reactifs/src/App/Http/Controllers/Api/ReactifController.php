<?php

namespace Modules\Reactifs\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Reactifs\App\Models\Reactif;
use Modules\Reactifs\App\Models\ReactifExamenType;
use Modules\Reactifs\App\Services\ReactifStockService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReactifController extends Controller
{
    public function __construct(
        private ReactifStockService $stockService
    ) {}

    public function index(): JsonResponse
    {
        $reactifs = Reactif::where('actif', true)
            ->orderBy('nom')
            ->paginate(20);

        $alertes = $this->stockService->getReactifsEnAlerte();

        return response()->json([
            'data'    => $reactifs,
            'alertes' => $alertes,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code'            => 'required|string|unique:reactifs,code',
            'nom'             => 'required|string|max:255',
            'unite'           => 'required|string|max:50',
            'stock_actuel'    => 'required|numeric|min:0',
            'stock_minimum'   => 'required|numeric|min:0',
            'stock_maximum'   => 'nullable|numeric|min:0',
            'localisation'    => 'nullable|string|max:255',
            'date_peremption' => 'nullable|date',
            'notes'           => 'nullable|string',
        ]);

        $reactif = Reactif::create($validated);

        return response()->json([
            'message' => "Réactif {$reactif->nom} créé avec succès.",
            'data'    => $reactif,
        ], 201);
    }

    public function show(Reactif $reactif): JsonResponse
    {
        $reactif->load(['mouvements' => fn($q) => $q->latest()->limit(20)]);
        $examenTypes = ReactifExamenType::where('reactif_id', $reactif->id)->get();

        return response()->json([
            'data'         => $reactif,
            'examen_types' => $examenTypes,
        ]);
    }

    public function update(Request $request, Reactif $reactif): JsonResponse
    {
        $validated = $request->validate([
            'code'            => 'required|string|unique:reactifs,code,' . $reactif->id,
            'nom'             => 'required|string|max:255',
            'unite'           => 'required|string|max:50',
            'stock_minimum'   => 'required|numeric|min:0',
            'stock_maximum'   => 'nullable|numeric|min:0',
            'localisation'    => 'nullable|string|max:255',
            'date_peremption' => 'nullable|date',
            'notes'           => 'nullable|string',
        ]);

        $reactif->update($validated);

        return response()->json([
            'message' => 'Réactif mis à jour.',
            'data'    => $reactif,
        ]);
    }

    public function destroy(Reactif $reactif): JsonResponse
    {
        $reactif->delete();

        return response()->json([
            'message' => 'Réactif supprimé.',
        ]);
    }

    public function getLiaisonExamenType(Reactif $reactif, int $examenTypeId): JsonResponse
    {
        $liaison = ReactifExamenType::where('reactif_id', $reactif->id)
            ->where('examen_type_id', $examenTypeId)
            ->first();

        if (!$liaison) {
            return response()->json([
                'message' => 'Aucune liaison trouvée pour ce type d\'examen.',
            ], 404);
        }

        return response()->json([
            'data' => $liaison,
        ]);
    }


    public function lierExamenType(Request $request, Reactif $reactif): JsonResponse
    {
        $validated = $request->validate([
            'examen_type_id'    => 'required|integer',
            'quantite_utilisee' => 'required|numeric|min:0.001',
            'unite'             => 'nullable|string|max:50',
            'notes'             => 'nullable|string',
        ]);

        $liaison = ReactifExamenType::updateOrCreate(
            [
                'reactif_id'     => $reactif->id,
                'examen_type_id' => $validated['examen_type_id'],
            ],
            [
                'quantite_utilisee' => $validated['quantite_utilisee'],
                'unite'             => $validated['unite'] ?? null,
                'notes'             => $validated['notes'] ?? null,
                'actif'             => true,
            ]
        );

        return response()->json([
            'message' => 'Liaison avec le type d\'examen enregistrée.',
            'data'    => $liaison,
        ]);
    }

    public function delierExamenType(Reactif $reactif, int $examenTypeId): JsonResponse
    {
        ReactifExamenType::where('reactif_id', $reactif->id)
            ->where('examen_type_id', $examenTypeId)
            ->delete();

        return response()->json([
            'message' => 'Liaison supprimée.',
        ]);
    }
}