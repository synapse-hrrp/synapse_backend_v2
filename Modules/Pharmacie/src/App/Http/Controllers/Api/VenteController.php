<?php

namespace Modules\Pharmacie\App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pharmacie\App\Interfaces\VenteInterface;
use Modules\Pharmacie\App\Models\Vente;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

class VenteController extends Controller
{
    public function __construct(
        private VenteInterface $venteService
    ) {}

    /**
     * Liste des ventes
     */
    public function index(Request $request): JsonResponse
    {
        $query = Vente::with([
            'depot:id,code,libelle',  // ✅ CORRIGÉ : libelle au lieu de nom
            'user:id,name,email',
            'lignes.produit'
        ])->orderBy('created_at', 'desc');

        if ($request->has('depot_id')) {
            $query->where('depot_id', $request->depot_id);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('date')) {
            $query->whereDate('date_vente', $request->date);
        }

        $ventes = $query->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Liste des ventes',
            'data' => $ventes
        ], 200);
    }

    /**
     * Détail d'une vente
     */
    public function show(int $id): JsonResponse
    {
        $vente = Vente::with([
            'depot',  // ✅ Pas de select, charge toutes les colonnes
            'user',
            'lignes.produit',
            'lignes.lots.stock'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Détail de la vente',
            'data' => $vente
        ], 200);
    }

    /**
     * Créer une vente (FEFO + blocage périmé + mouvements)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'depot_id' => 'required|exists:depots,id',
            'type' => 'required|in:VENTE,GRATUITE',
            'observations' => 'nullable|string',
            'lignes' => 'required|array|min:1',
            'lignes.*.produit_id' => 'required|exists:produits,id',
            'lignes.*.quantite' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $vente = $this->venteService->creerVente($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Vente créée avec succès',
                'data' => $vente
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => null
            ], 400);
        }
    }

    /**
     * Annuler une vente (remise en stock sur mêmes lots)
     */
    public function annuler(int $id): JsonResponse
    {
        try {
            $result = $this->venteService->annulerVente($id);

            return response()->json([
                'success' => true,
                'message' => 'Vente annulée avec succès',
                'data' => null
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => null
            ], 400);
        }
    }

    /**
     * Valider une vente (EN_ATTENTE → PAYEE)
     */
    public function valider(int $id): JsonResponse
    {
        try {
            $vente = Vente::findOrFail($id);

            if ($vente->statut !== 'EN_ATTENTE') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seules les ventes EN_ATTENTE peuvent être validées',
                ], 400);
            }

            // ✅ total payé fiable depuis Finance
            $totalPaye = (float) DB::table('t_finance_paiements')
                ->where('module_source', 'pharmacie')
                ->where('table_source', 'ventes')
                ->where('source_id', $vente->id)
                ->where('statut', 'valide')
                ->sum('montant');

            $totalDu = (float) $vente->montant_ttc;

            if ($totalDu > 0 && $totalPaye + 0.01 < $totalDu) {
                return response()->json([
                    'success' => false,
                    'message' => "Impossible de valider: vente non soldée. Payé={$totalPaye}, Total={$totalDu}.",
                ], 409);
            }

            $vente->update(['statut' => 'PAYEE']);

            return response()->json([
                'success' => true,
                'message' => 'Vente validée avec succès',
                'data' => $vente
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}