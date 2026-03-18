<?php

namespace Modules\Pharmacie\App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pharmacie\App\Models\Fournisseur;
use Illuminate\Support\Facades\Validator;

class FournisseurController extends Controller
{
    /**
     * Liste des fournisseurs
     */
    public function index(): JsonResponse
    {
        $fournisseurs = Fournisseur::where('actif', true)->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste des fournisseurs',
            'data' => $fournisseurs
        ], 200);
    }

    /**
     * Créer un fournisseur
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:150',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'adresse' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $fournisseur = Fournisseur::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Fournisseur créé avec succès',
            'data' => $fournisseur
        ], 201);
    }

    /**
     * Afficher un fournisseur
     */
    public function show(int $id): JsonResponse
    {
        $fournisseur = Fournisseur::find($id);

        if (!$fournisseur) {
            return response()->json([
                'success' => false,
                'message' => 'Fournisseur non trouvé',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Détails du fournisseur',
            'data' => $fournisseur
        ], 200);
    }

    /**
     * Mettre à jour un fournisseur
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $fournisseur = Fournisseur::find($id);

        if (!$fournisseur) {
            return response()->json([
                'success' => false,
                'message' => 'Fournisseur non trouvé',
                'data' => null
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:150',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'adresse' => 'nullable|string',
            'actif' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $fournisseur->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Fournisseur mis à jour avec succès',
            'data' => $fournisseur
        ], 200);
    }

    /**
     * Supprimer un fournisseur
     */
    public function destroy(int $id): JsonResponse
    {
        $fournisseur = Fournisseur::find($id);

        if (!$fournisseur) {
            return response()->json([
                'success' => false,
                'message' => 'Fournisseur non trouvé',
                'data' => null
            ], 404);
        }

        $fournisseur->update(['actif' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Fournisseur désactivé avec succès',
            'data' => null
        ], 200);
    }
}