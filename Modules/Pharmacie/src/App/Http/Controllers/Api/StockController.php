<?php

namespace Modules\Pharmacie\App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pharmacie\App\Interfaces\StockInterface;
use Modules\Pharmacie\App\Models\Stock;
use Modules\Pharmacie\App\Notifications\StockPeremptionProche;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockController extends Controller
{
    public function __construct(
        private StockInterface $stockService
    ) {}

    /**
     * Liste des stocks (filtrable par produit/dépôt)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Stock::with(['produit', 'depot'])
            ->where('quantite', '>', 0);

        if ($request->has('produit_id')) {
            $query->where('produit_id', $request->produit_id);
        }

        if ($request->has('depot_id')) {
            $query->where('depot_id', $request->depot_id);
        }

        $stocks = $query->orderBy('date_peremption', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste des stocks',
            'data' => $stocks
        ], 200);
    }

    /**
     * Créer un nouveau stock avec TVA
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'produit_id' => 'required|exists:produits,id',
                'depot_id' => 'required|exists:depots,id',
                'numero_lot' => 'required|string|max:100',
                'date_peremption' => 'required|date|after:today',
                'quantite' => 'required|integer|min:1',
                'prix_achat_unitaire_ht' => 'required|numeric|min:0',
                'taux_tva' => 'nullable|numeric|min:0|max:100',
            ]);

            // Définir taux_tva par défaut si non fourni
            if (!isset($validated['taux_tva'])) {
                $validated['taux_tva'] = 18.9;
            }

            // Créer le stock (la TVA sera calculée automatiquement via boot())
            $stock = Stock::create($validated);

            // Charger les relations
            $stock->load(['produit', 'depot']);

            return response()->json([
                'success' => true,
                'message' => 'Stock créé avec succès',
                'data' => $stock
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du stock',
                'errors' => ['server' => [$e->getMessage()]]
            ], 500);
        }
    }

    /**
     * Afficher un stock spécifique
     */
    public function show($id): JsonResponse
    {
        $stock = Stock::with(['produit', 'depot'])->find($id);

        if (!$stock) {
            return response()->json([
                'success' => false,
                'message' => 'Stock non trouvé',
                'errors' => ['stock' => ['Le stock avec l\'ID ' . $id . ' n\'existe pas']]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Détails du stock',
            'data' => $stock
        ]);
    }

    /**
     * Mettre à jour un stock
     */
    public function update(Request $request, $id): JsonResponse
    {
        $stock = Stock::find($id);

        if (!$stock) {
            return response()->json([
                'success' => false,
                'message' => 'Stock non trouvé',
                'errors' => ['stock' => ['Le stock avec l\'ID ' . $id . ' n\'existe pas']]
            ], 404);
        }

        try {
            $validated = $request->validate([
                'numero_lot' => 'sometimes|string|max:100',
                'date_peremption' => 'sometimes|date',
                'quantite' => 'sometimes|integer|min:0',
                'prix_achat_unitaire_ht' => 'sometimes|numeric|min:0',
                'taux_tva' => 'sometimes|numeric|min:0|max:100',
            ]);

            $stock->update($validated);
            $stock->load(['produit', 'depot']);

            return response()->json([
                'success' => true,
                'message' => 'Stock modifié avec succès',
                'data' => $stock
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * ✅ NOUVEAU : Modifier le prix de vente d'un stock
     * PUT /api/v1/pharmacie/stocks/{id}/prix-vente
     */
    public function modifierPrixVente(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'prix_vente_unitaire_ttc' => 'required|numeric|min:0',
            'motif' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $stock = Stock::with(['produit', 'depot'])->find($id);

        if (!$stock) {
            return response()->json([
                'success' => false,
                'message' => 'Stock non trouvé',
            ], 404);
        }

        // Sauvegarder l'ancien prix pour l'audit
        $ancienPrixVente = $stock->prix_vente_unitaire_ttc;
        
        $prixVenteTtc = $request->input('prix_vente_unitaire_ttc');
        $tva = $stock->taux_tva ?? 18.00;
        
        // Vérifier que le nouveau prix est >= prix d'achat (éviter les pertes)
        if ($prixVenteTtc < $stock->prix_achat_unitaire_ttc) {
            return response()->json([
                'success' => false,
                'message' => 'Le prix de vente ne peut pas être inférieur au prix d\'achat',
                'errors' => [
                    'prix_vente_unitaire_ttc' => [
                        "Le prix de vente minimum doit être {$stock->prix_achat_unitaire_ttc} FCFA"
                    ]
                ]
            ], 422);
        }
        
        // Calculer le prix HT et les marges
        $prixVenteHt = round($prixVenteTtc / (1 + ($tva / 100)), 2);
        $margeHt = round($prixVenteHt - $stock->prix_achat_unitaire_ht, 2);
        $margeTtc = round($prixVenteTtc - $stock->prix_achat_unitaire_ttc, 2);
        $tauxMarge = $stock->prix_achat_unitaire_ht > 0 
            ? round(($margeHt / $stock->prix_achat_unitaire_ht) * 100, 2)
            : 0;

        // Mettre à jour le stock
        $stock->update([
            'prix_vente_unitaire_ttc' => $prixVenteTtc,
            'prix_vente_unitaire_ht' => $prixVenteHt,
            'marge_unitaire_ht' => $margeHt,
            'marge_unitaire_ttc' => $margeTtc,
            'taux_marge' => $tauxMarge,
        ]);

        // Créer un audit log
        if ($ancienPrixVente != $prixVenteTtc) {
            try {
                DB::table('pharmacie_audits')->insert([
                    'auditable_type' => 'Stock',
                    'auditable_id' => $stock->id,
                    'event' => 'prix_vente_modifie',
                    'old_values' => json_encode([
                        'prix_vente_unitaire_ttc' => $ancienPrixVente,
                    ]),
                    'new_values' => json_encode([
                        'prix_vente_unitaire_ttc' => $prixVenteTtc,
                        'marge_unitaire_ttc' => $margeTtc,
                        'taux_marge' => $tauxMarge,
                        'motif' => $request->input('motif'),
                    ]),
                    'user_id' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                // Si la table d'audit n'existe pas, continuer sans erreur
                \Log::info('Audit log non créé: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Prix de vente modifié avec succès',
            'data' => [
                'stock' => $stock,
                'ancien_prix_vente' => $ancienPrixVente,
                'nouveau_prix_vente' => $prixVenteTtc,
                'nouvelle_marge_ttc' => $margeTtc,
                'nouveau_taux_marge' => $tauxMarge,
            ]
        ]);
    }

    /**
     * Supprimer un stock
     */
    public function destroy($id): JsonResponse
    {
        $stock = Stock::find($id);

        if (!$stock) {
            return response()->json([
                'success' => false,
                'message' => 'Stock non trouvé',
                'errors' => ['stock' => ['Le stock avec l\'ID ' . $id . ' n\'existe pas']]
            ], 404);
        }

        $stock->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stock supprimé avec succès',
            'data' => null
        ]);
    }

    /**
     * Stocks périmés
     */
    public function perimes(): JsonResponse
    {
        $stocks = $this->stockService->getStocksPerimes();

        return response()->json([
            'success' => true,
            'message' => 'Stocks périmés',
            'data' => $stocks
        ], 200);
    }

    /**
     * Stocks proches de la péremption (30 jours)
     */
    public function proches(): JsonResponse
    {
        $stocks = $this->stockService->getStocksProches(30);

        return response()->json([
            'success' => true,
            'message' => 'Stocks proches de la péremption (30 jours)',
            'data' => $stocks
        ], 200);
    }

    /**
     * Stocks proches péremption avec paramètre jours
     */
    public function prochesPeremption(Request $request): JsonResponse
    {
        $jours = $request->input('jours', 30);

        $stocks = Stock::with(['produit', 'depot'])
            ->disponibles()
            ->proche($jours)
            ->get()
            ->map(function ($stock) {
                $stock->jours_restants = $stock->jours_avant_peremption;
                $stock->statut = $stock->statut_peremption;
                return $stock;
            });

        return response()->json([
            'success' => true,
            'message' => "Stocks proches de la péremption ({$jours} jours)",
            'data' => $stocks
        ]);
    }

    /**
     * Stocks en bon état (> 30 jours)
     */
    public function bon(): JsonResponse
    {
        $stocks = $this->stockService->getStocksBon(30);

        return response()->json([
            'success' => true,
            'message' => 'Stocks en bon état',
            'data' => $stocks
        ], 200);
    }

    /**
     * Stocks sous seuil minimum
     */
    public function seuilMin(): JsonResponse
    {
        $seuils = $this->stockService->verifierSeuils();
        
        $sousMin = $seuils->filter(function ($item) {
            return $item->quantite_totale < $item->seuil_min;
        });

        return response()->json([
            'success' => true,
            'message' => 'Stocks sous seuil minimum (réappro nécessaire)',
            'data' => $sousMin->values()
        ], 200);
    }

    /**
     * Stocks au-dessus seuil maximum
     */
    public function seuilMax(): JsonResponse
    {
        $seuils = $this->stockService->verifierSeuils();
        
        $surMax = $seuils->filter(function ($item) {
            return $item->quantite_totale > $item->seuil_max;
        });

        return response()->json([
            'success' => true,
            'message' => 'Stocks au-dessus du seuil maximum (surstock)',
            'data' => $surMax->values()
        ], 200);
    }

    /**
     * Stocks par dépôt
     */
    public function parDepot($depot_id): JsonResponse
    {
        $stocks = Stock::with(['produit', 'depot'])
            ->where('depot_id', $depot_id)
            ->disponibles()
            ->nonPerime()
            ->orderBy('date_peremption', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Stocks du dépôt',
            'data' => $stocks
        ]);
    }

    /**
     * Stocks par produit
     */
    public function parProduit($produit_id): JsonResponse
    {
        $stocks = Stock::with(['produit', 'depot'])
            ->where('produit_id', $produit_id)
            ->disponibles()
            ->nonPerime()
            ->orderBy('date_peremption', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Stocks du produit',
            'data' => $stocks
        ]);
    }

    /**
     * Statistiques globales des stocks avec TVA
     */
    public function statistiques(): JsonResponse
    {
        $stocksDisponibles = Stock::disponibles()->get();
        
        $stats = [
            'total_stocks' => $stocksDisponibles->count(),
            'valeur_totale_ht' => round($stocksDisponibles->sum('valeur_totale_ht'), 2),
            'valeur_totale_ttc' => round($stocksDisponibles->sum('valeur_totale_ttc'), 2),
            'montant_tva_total' => round($stocksDisponibles->sum('valeur_totale_ttc') - $stocksDisponibles->sum('valeur_totale_ht'), 2),
            'stocks_proches_peremption' => Stock::disponibles()->proche(30)->count(),
            'stocks_perimes' => Stock::perime()->count(),
            'produits_differents' => Stock::disponibles()->distinct('produit_id')->count(),
            'depots_utilises' => Stock::disponibles()->distinct('depot_id')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Statistiques des stocks',
            'data' => $stats
        ]);
    }

    /**
     * Test envoi notification (pour développement)
     */
    public function envoyerAlerteTest(): JsonResponse
    {
        $stocksProches = $this->stockService->getStocksProches(30);
        
        if ($stocksProches->count() > 0) {
            Auth::user()->notify(new StockPeremptionProche($stocksProches));
            
            return response()->json([
                'success' => true,
                'message' => 'Email de test envoyé avec ' . $stocksProches->count() . ' stock(s)',
                'data' => [
                    'nombre_stocks' => $stocksProches->count(),
                    'email_envoye_a' => Auth::user()->email
                ]
            ], 200);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Aucun stock proche de la péremption pour le test',
            'data' => null
        ], 200);
    }
}