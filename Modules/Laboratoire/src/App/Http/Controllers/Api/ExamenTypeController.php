<?php

namespace Modules\Laboratoire\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Laboratoire\App\Models\ExamenType;

class ExamenTypeController extends Controller
{
    /**
     * GET /api/v1/laboratoire/examen-types
     * Liste tous les types d'examens
     */
    public function index(Request $request): JsonResponse
    {
        $query = ExamenType::query();

        if ($request->boolean('actifs')) {
            $query->actifs();
        }

        if ($request->filled('categorie')) {
            $query->where('categorie', $request->categorie);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nom', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        }

        $examenTypes = $query->orderBy('nom')->get();

        return response()->json([
            'success' => true,
            'data'    => $examenTypes,
        ]);
    }

    /**
     * POST /api/v1/laboratoire/examen-types
     * Créer un nouveau type d'examen
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom'           => ['required', 'string', 'max:255'],
            'code'          => ['required', 'string', 'max:50', 'unique:examen_types,code'],
            'categorie'     => ['nullable', 'string', 'max:100'],
            'delai_heures'  => ['nullable', 'integer', 'min:0'],
            'instructions'  => ['nullable', 'string'],
            'active'        => ['boolean'],
        ]);

        $examenType = ExamenType::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Type d\'examen créé avec succès.',
            'data'    => $examenType,
        ], 201);
    }

    /**
     * GET /api/v1/laboratoire/examen-types/{examenType}
     * Afficher un type d'examen
     */
    public function show(ExamenType $examenType): JsonResponse
    {
        $examenType->load('parametres');

        return response()->json([
            'success' => true,
            'data'    => $examenType,
        ]);
    }

    /**
     * PUT /api/v1/laboratoire/examen-types/{examenType}
     * Mettre à jour un type d'examen
     */
    public function update(Request $request, ExamenType $examenType): JsonResponse
    {
        $validated = $request->validate([
            'nom'           => ['sometimes', 'required', 'string', 'max:255'],
            'code'          => ['sometimes', 'required', 'string', 'max:50', 'unique:examen_types,code,' . $examenType->id],
            'categorie'     => ['nullable', 'string', 'max:100'],
            'delai_heures'  => ['nullable', 'integer', 'min:0'],
            'instructions'  => ['nullable', 'string'],
            'active'        => ['boolean'],
        ]);

        $examenType->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Type d\'examen mis à jour avec succès.',
            'data'    => $examenType,
        ]);
    }

    /**
     * DELETE /api/v1/laboratoire/examen-types/{examenType}
     * Supprimer un type d'examen (soft delete)
     */
    public function destroy(ExamenType $examenType): JsonResponse
    {
        $examenType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Type d\'examen supprimé avec succès.',
        ]);
    }

    /**
     * PUT /api/v1/laboratoire/examen-types/{examenType}/toggle-active
     * Activer / Désactiver un type d'examen
     */
    public function toggleActive(ExamenType $examenType): JsonResponse
    {
        $examenType->update(['active' => !$examenType->active]);

        return response()->json([
            'success' => true,
            'message' => $examenType->active
                ? 'Type d\'examen activé avec succès.'
                : 'Type d\'examen désactivé avec succès.',
            'data'    => $examenType,
        ]);
    }
}