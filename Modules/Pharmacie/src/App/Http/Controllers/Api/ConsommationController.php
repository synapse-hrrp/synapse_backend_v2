<?php

namespace Modules\Pharmacie\App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pharmacie\App\Services\ConsommationAnalyseService;
use Illuminate\Support\Facades\Validator;

class ConsommationController extends Controller
{
    public function __construct(
        private ConsommationAnalyseService $consommationService
    ) {}

    /**
     * Analyser consommation d'un produit dans un dépôt
     * GET/POST /api/v1/pharmacie/consommations/analyser
     */
    public function analyser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'produit_id' => 'required|exists:produits,id',
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
            $analyse = $this->consommationService->analyserConsommation(
                $request->produit_id,
                $request->depot_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Analyse de consommation',
                'data' => $analyse
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Analyser tous les produits avec commande auto activée
     * POST /api/v1/pharmacie/consommations/analyser-tous
     */
    public function analyserTous(): JsonResponse
    {
        try {
            $resultats = $this->consommationService->analyserTous();

            return response()->json([
                'success' => true,
                'message' => 'Analyse de tous les produits avec commande auto',
                'data' => [
                    'total_analyses' => count($resultats),
                    'resultats' => $resultats,
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
     * Enregistrer consommation hebdomadaire manuelle
     * POST /api/v1/pharmacie/consommations/enregistrer-semaine
     */
    public function enregistrerSemaine(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'produit_id' => 'required|exists:produits,id',
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
            $this->consommationService->enregistrerConsommationSemaine(
                $request->produit_id,
                $request->depot_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Consommation hebdomadaire enregistrée',
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
     * Détecter surconsommation
     * GET /api/v1/pharmacie/consommations/surconsommation
     */
    public function detecterSurconsommation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'produit_id' => 'required|exists:produits,id',
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
            $surconsommation = $this->consommationService->detecterSurconsommation(
                $request->produit_id,
                $request->depot_id
            );

            return response()->json([
                'success' => true,
                'message' => $surconsommation ? 'Surconsommation détectée' : 'Pas de surconsommation',
                'data' => [
                    'surconsommation_detectee' => $surconsommation,
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