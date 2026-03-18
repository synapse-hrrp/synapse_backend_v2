<?php

namespace Modules\Pharmacie\App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pharmacie\App\Services\ExportService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    public function __construct(
        private ExportService $exportService
    ) {}

    /**
     * Exporter une commande
     * POST /api/v1/pharmacie/commandes/{id}/exporter
     */
    public function exporterCommande(Request $request, int $id): JsonResponse
    {
        // ✅ Accepter MAJUSCULE et minuscule
        $validator = Validator::make($request->all(), [
            'format' => 'required|string|in:EXCEL,CSV,PDF,excel,csv,pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // ✅ Normaliser en MAJUSCULE
            $format = strtoupper($request->format);
            
            $result = $this->exportService->exporterCommande(
                $id,
                $format,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Commande exportée avec succès',
                'data' => [
                    'commande_id' => $id,
                    'format' => $format,
                    'fichier_nom' => basename($result['path']),
                    'fichier_path' => $result['path'],
                    'fichier_url' => Storage::url($result['path']),
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
     * Exporter une réception
     * POST /api/v1/pharmacie/receptions/{id}/exporter
     */
    public function exporterReception(Request $request, int $id): JsonResponse
    {
        // ✅ Accepter MAJUSCULE et minuscule
        $validator = Validator::make($request->all(), [
            'format' => 'required|string|in:EXCEL,CSV,PDF,excel,csv,pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // ✅ Normaliser en MAJUSCULE
            $format = strtoupper($request->format);
            
            $result = $this->exportService->exporterReception(
                $id,
                $format,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Réception exportée avec succès',
                'data' => [
                    'reception_id' => $id,
                    'format' => $format,
                    'fichier_nom' => basename($result['path']),
                    'fichier_path' => $result['path'],
                    'fichier_url' => Storage::url($result['path']),
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
     * ✅ BONUS : Télécharger directement un fichier exporté
     * GET /api/v1/pharmacie/exports/download?path=exports/commandes/xxx.xlsx
     */
    public function telecharger(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $path = $request->path;

            // Vérifier que le fichier existe
            if (!Storage::exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier non trouvé',
                ], 404);
            }

            // Vérifier que c'est bien un fichier d'export (sécurité)
            if (!str_starts_with($path, 'exports/')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé',
                ], 403);
            }

            return response()->download(
                storage_path("app/{$path}"),
                basename($path)
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
