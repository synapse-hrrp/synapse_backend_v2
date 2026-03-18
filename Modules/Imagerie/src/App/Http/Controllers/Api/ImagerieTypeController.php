<?php

namespace Modules\Imagerie\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Imagerie\App\Models\ImagerieType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ImagerieTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $types = ImagerieType::actifs()
            ->orderBy('nom')
            ->paginate(20);

        return response()->json([
            'data' => $types,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom'                => 'required|string|max:255',
            'code'               => 'required|string|unique:imagerie_types,code',
            'categorie'          => 'required|string|max:100',
            'delai_heures'       => 'nullable|integer|min:0',
            'preparation'        => 'nullable|string',
            'contre_indications' => 'nullable|string',
            'active'             => 'boolean',
        ]);

        $type = ImagerieType::create($validated);

        return response()->json([
            'message' => "Type d'imagerie {$type->nom} créé avec succès.",
            'data'    => $type,
        ], 201);
    }

    public function show(ImagerieType $imagerieType): JsonResponse
    {
        return response()->json([
            'data' => $imagerieType,
        ]);
    }

    public function update(Request $request, ImagerieType $imagerieType): JsonResponse
    {
        $validated = $request->validate([
            'nom'                => 'required|string|max:255',
            'code'               => 'required|string|unique:imagerie_types,code,' . $imagerieType->id,
            'categorie'          => 'required|string|max:100',
            'delai_heures'       => 'nullable|integer|min:0',
            'preparation'        => 'nullable|string',
            'contre_indications' => 'nullable|string',
            'active'             => 'boolean',
        ]);

        $imagerieType->update($validated);

        return response()->json([
            'message' => "Type d'imagerie mis à jour.",
            'data'    => $imagerieType,
        ]);
    }

    public function destroy(ImagerieType $imagerieType): JsonResponse
    {
        // Vérifier si des demandes existent
        if ($imagerieType->imagerieRequests()->count() > 0) {
            $imagerieType->update(['active' => false]);

            return response()->json([
                'message' => "Type d'imagerie désactivé car il possède des demandes.",
                'data'    => $imagerieType,
            ]);
        }

        $imagerieType->delete();

        return response()->json([
            'message' => "Type d'imagerie supprimé.",
        ]);
    }
}