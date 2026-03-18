<?php

namespace Modules\Pharmacie\App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pharmacie\App\Services\CodeBarreService;
use Illuminate\Support\Facades\Validator;

class ScanController extends Controller
{
    public function __construct(
        private CodeBarreService $codeBarreService
    ) {}

    /**
     * Scanner un code-barres
     * POST /api/v1/pharmacie/scan
     */
    public function scanner(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code_barre' => 'required|string',
            'depot_id' => 'nullable|exists:depots,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $resultat = $this->codeBarreService->scanner(
                $request->code_barre,
                $request->depot_id
            );

            return response()->json($resultat, $resultat['success'] ? 200 : 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Scanner plusieurs codes-barres (inventaire)
     * POST /api/v1/pharmacie/scan/multiple
     */
    public function scannerMultiple(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'codes_barres' => 'required|array|min:1',
            'codes_barres.*' => 'required|string',
            'depot_id' => 'required|exists:depots,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $resultats = $this->codeBarreService->scannerMultiple(
                $request->codes_barres,
                $request->depot_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Scan multiple terminé',
                'data' => $resultats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Vérifier disponibilité pour vente
     * POST /api/v1/pharmacie/scan/verifier-disponibilite
     */
    public function verifierDisponibilite(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code_barre' => 'required|string',
            'quantite' => 'required|integer|min:1',
            'depot_id' => 'required|exists:depots,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $resultat = $this->codeBarreService->verifierDisponibilitePourVente(
                $request->code_barre,
                $request->quantite,
                $request->depot_id
            );

            return response()->json([
                'success' => $resultat['disponible'],
                'message' => $resultat['disponible'] ? 'Produit disponible' : $resultat['message'],
                'data' => $resultat
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Associer un code-barres à un produit
     * POST /api/v1/pharmacie/scan/associer
     */
    public function associerCodeBarre(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'produit_id' => 'required|exists:produits,id',
            'code_barre' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $resultat = $this->codeBarreService->associerCodeBarre(
                $request->produit_id,
                $request->code_barre
            );

            return response()->json([
                'success' => true,
                'message' => 'Code-barres associé avec succès',
                'data' => null
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Générer code-barres interne
     * POST /api/v1/pharmacie/scan/generer-code-interne
     */
    public function genererCodeInterne(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'produit_id' => 'required|exists:produits,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $codeBarre = $this->codeBarreService->genererCodeBarreInterne($request->produit_id);

            return response()->json([
                'success' => true,
                'message' => 'Code-barres interne généré',
                'data' => [
                    'code_barre' => $codeBarre,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Rechercher produits similaires (si scan échoue)
     * GET /api/v1/pharmacie/scan/rechercher
     */
    public function rechercherSimilaires(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'recherche' => 'required|string|min:2',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $resultats = $this->codeBarreService->rechercherSimilaires(
                $request->recherche,
                $request->get('limit', 10)
            );

            return response()->json([
                'success' => true,
                'message' => 'Résultats de recherche',
                'data' => $resultats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Valider un code EAN-13
     * POST /api/v1/pharmacie/scan/valider-ean13
     */
    public function validerEAN13(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code_barre' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $valide = $this->codeBarreService->validerEAN13($request->code_barre);

            return response()->json([
                'success' => true,
                'message' => $valide ? 'Code EAN-13 valide' : 'Code EAN-13 invalide',
                'data' => [
                    'valide' => $valide,
                    'code_barre' => $request->code_barre,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}