<?php

namespace Modules\Pharmacie\App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pharmacie\App\Services\CommandeAutoService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CommandeAutoController extends Controller
{
    public function __construct(
        private CommandeAutoService $commandeAutoService
    ) {}

    /**
     * Vérifier tous les produits et déclencher commandes auto
     * POST /api/v1/pharmacie/commandes-auto/verifier-tous
     */
    public function verifierTous(): JsonResponse
    {
        try {
            $resultats = $this->commandeAutoService->verifierTousLesProduits();

            return response()->json([
                'success' => true,
                'message' => 'Vérification de tous les produits terminée',
                'data' => [
                    'commandes_declenchees' => count($resultats),
                    'details' => $resultats,
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
     * Vérifier un produit spécifique dans un dépôt
     * POST /api/v1/pharmacie/commandes-auto/verifier
     */
    public function verifier(Request $request): JsonResponse
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
            $resultat = $this->commandeAutoService->verifierEtDeclencher(
                $request->produit_id,
                $request->depot_id
            );

            return response()->json([
                'success' => true,
                'message' => $resultat['commande_declenchee'] 
                    ? 'Commande automatique déclenchée' 
                    : 'Aucune commande nécessaire',
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
     * Liste des commandes auto en attente de validation
     * GET /api/v1/pharmacie/commandes-auto/en-attente
     */
    public function enAttente(): JsonResponse
    {
        try {
            $commandes = $this->commandeAutoService->getCommandesEnAttenteValidation();

            return response()->json([
                'success' => true,
                'message' => 'Commandes automatiques en attente de validation',
                'data' => $commandes
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Valider une commande automatique
     * POST /api/v1/pharmacie/commandes-auto/{id}/valider
     */
    public function valider(int $id): JsonResponse
    {
        try {
            $resultat = $this->commandeAutoService->validerCommandeAuto($id, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Commande automatique validée avec succès',
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
     * Statistiques commandes auto
     * GET /api/v1/pharmacie/commandes-auto/statistiques
     */
    public function statistiques(): JsonResponse
    {
        try {
            $stats = $this->commandeAutoService->getStatistiques();

            return response()->json([
                'success' => true,
                'message' => 'Statistiques commandes automatiques',
                'data' => $stats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}