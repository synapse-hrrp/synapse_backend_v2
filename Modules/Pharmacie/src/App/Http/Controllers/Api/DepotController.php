<?php

namespace Modules\Pharmacie\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Pharmacie\App\Models\Depot;

class DepotController extends Controller
{
    /**
     * GET /api/v1/pharmacie/depots
     * Liste des dépôts (par défaut actifs uniquement)
     * Option: ?all=1 pour inclure inactifs
     */
    public function index(Request $request): JsonResponse
    {
        $q = Depot::query();

        if (!$request->boolean('all')) {
            $q->where('actif', true);
        }

        $depots = $q->orderBy('libelle')->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste des dépôts',
            'data' => $depots
        ], 200);
    }

    /**
     * GET /api/v1/pharmacie/depots/{id}
     */
    public function show(int $id): JsonResponse
    {
        $depot = Depot::find($id);

        if (!$depot) {
            return response()->json([
                'success' => false,
                'message' => 'Dépôt non trouvé',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Détail du dépôt',
            'data' => $depot
        ], 200);
    }

    /**
     * POST /api/v1/pharmacie/depots
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:20|unique:depots,code',
            'libelle' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'actif' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $depot = Depot::create([
            'code' => strtoupper(trim($request->input('code'))),
            'libelle' => trim($request->input('libelle')),
            'description' => $request->input('description'),
            'actif' => $request->has('actif') ? (bool)$request->input('actif') : true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dépôt créé avec succès',
            'data' => $depot
        ], 201);
    }

    /**
     * PUT /api/v1/pharmacie/depots/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $depot = Depot::find($id);

        if (!$depot) {
            return response()->json([
                'success' => false,
                'message' => 'Dépôt non trouvé',
                'data' => null
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|required|string|max:20|unique:depots,code,' . $depot->id,
            'libelle' => 'sometimes|required|string|max:150',
            'description' => 'nullable|string|max:500',
            'actif' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('code')) $depot->code = strtoupper(trim($request->input('code')));
        if ($request->has('libelle')) $depot->libelle = trim($request->input('libelle'));
        if ($request->has('description')) $depot->description = $request->input('description');
        if ($request->has('actif')) $depot->actif = (bool)$request->input('actif');

        $depot->save();

        return response()->json([
            'success' => true,
            'message' => 'Dépôt modifié avec succès',
            'data' => $depot
        ], 200);
    }

    /**
     * DELETE /api/v1/pharmacie/depots/{id}
     * Delete SAFE => actif=false
     */
    public function destroy(int $id): JsonResponse
    {
        $depot = Depot::find($id);

        if (!$depot) {
            return response()->json([
                'success' => false,
                'message' => 'Dépôt non trouvé',
                'data' => null
            ], 404);
        }

        // Désactivation plutôt que suppression
        $depot->actif = false;
        $depot->save();

        return response()->json([
            'success' => true,
            'message' => 'Dépôt désactivé avec succès',
            'data' => null
        ], 200);
    }
}
