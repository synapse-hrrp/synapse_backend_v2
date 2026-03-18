<?php

namespace Modules\Pharmacie\App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pharmacie\App\Models\Produit;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProduitController extends Controller
{
    /**
     * Liste des produits
     */
    public function index(): JsonResponse
    {
        $produits = Produit::where('actif', true)->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste des produits',
            'data' => $produits
        ], 200);
    }

    /**
     * Créer un produit
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:produits,code',
            'nom' => 'required|string|max:200',
            'code_barre' => 'nullable|string|max:50|unique:produits,code_barre',
            'nom_commercial' => 'nullable|string|max:255',
            'molecule' => 'nullable|string|max:255',
            'fabricant_id' => 'nullable|exists:fabricants,id',
            'categorie_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'forme' => 'nullable|string|max:50',
            'dosage' => 'nullable|string|max:50',
            'prix_vente_conseille' => 'nullable|numeric|min:0',
            'coefficient_marge_defaut' => 'nullable|numeric|min:1',
            'commande_automatique' => 'nullable|boolean',
            'delai_livraison_jours' => 'nullable|integer|min:1',
            'unite_vente' => 'nullable|in:UNITE,BOITE,STRIP,FLACON',
            'unites_par_boite' => 'nullable|integer|min:1',
            'actif' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $produit = Produit::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Produit créé avec succès',
            'data' => $produit
        ], 201);
    }

    /**
     * ✅ Afficher un produit - SEULEMENT les infos du produit
     */
    public function show(int $id): JsonResponse
    {
        $produit = Produit::with(['fabricant', 'categorie'])->find($id);

        if (!$produit) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Détails du produit',
            'data' => $produit
        ], 200);
    }

    /**
     * ✅ NOUVEAU : Récupérer les infos STOCK du produit
     */
    public function getStock(int $id): JsonResponse
    {
        $produit = Produit::find($id);

        if (!$produit) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé',
                'data' => null
            ], 404);
        }

        // Calculer le stock total
        $stockTotal = DB::table('stocks')
            ->where('produit_id', $id)
            ->sum('quantite');

        // Récupérer les lots
        $lots = DB::table('stocks')
            ->where('produit_id', $id)
            ->where('quantite', '>', 0)
            ->select([
                'numero_lot',
                'quantite',
                'date_peremption',
                'prix_achat_unitaire_ttc',
                'prix_vente_unitaire_ttc'
            ])
            ->orderBy('date_peremption', 'asc')
            ->get();

        // Calculer valeurs totales
        $valeurStockAchat = DB::table('stocks')
            ->where('produit_id', $id)
            ->whereNotNull('prix_achat_unitaire_ttc')
            ->selectRaw('SUM(quantite * prix_achat_unitaire_ttc) as total')
            ->value('total') ?? 0;

        $valeurStockVente = DB::table('stocks')
            ->where('produit_id', $id)
            ->whereNotNull('prix_vente_unitaire_ttc')
            ->selectRaw('SUM(quantite * prix_vente_unitaire_ttc) as total')
            ->value('total') ?? 0;

        return response()->json([
            'success' => true,
            'message' => 'Informations stock du produit',
            'data' => [
                'produit_id' => $id,
                'stock_total' => (int) $stockTotal,
                'valeur_stock_achat_ttc' => (float) $valeurStockAchat,
                'valeur_stock_vente_ttc' => (float) $valeurStockVente,
                'lots' => $lots
            ]
        ]);
    }

    /**
     * ✅ CORRIGÉ : Récupérer l'historique des PRIX (réceptions)
     */
    public function getHistoriquePrix(int $id): JsonResponse
    {
        $produit = Produit::find($id);

        if (!$produit) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé',
                'data' => null
            ], 404);
        }

        // ✅ Dernière réception AVEC prix (pas NULL)
        $derniereReception = DB::table('ligne_receptions as lr')
            ->join('receptions as r', 'r.id', '=', 'lr.reception_id')
            ->join('fournisseurs as f', 'f.id', '=', 'r.fournisseur_id')
            ->where('lr.produit_id', $id)
            ->whereNotNull('lr.prix_achat_unitaire_ttc')
            ->whereNotNull('lr.prix_vente_unitaire_ttc')
            ->orderBy('r.date_reception', 'desc')
            ->select([
                'r.date_reception',
                'lr.prix_achat_unitaire_ht',
                'lr.prix_achat_unitaire_ttc',
                'lr.prix_vente_unitaire_ttc',
                'lr.tva_pourcentage',
                'lr.coefficient_marge',
                'lr.marge_prevue_ttc',
                'lr.taux_marge_prevu',
                'f.nom as fournisseur'
            ])
            ->first();

        // ✅ Historique des 10 dernières réceptions AVEC prix
        $historique = DB::table('ligne_receptions as lr')
            ->join('receptions as r', 'r.id', '=', 'lr.reception_id')
            ->join('fournisseurs as f', 'f.id', '=', 'r.fournisseur_id')
            ->where('lr.produit_id', $id)
            ->whereNotNull('lr.prix_achat_unitaire_ttc')
            ->whereNotNull('lr.prix_vente_unitaire_ttc')
            ->orderBy('r.date_reception', 'desc')
            ->limit(10)
            ->select([
                'r.numero as numero_reception',
                'r.date_reception',
                'lr.quantite',
                'lr.prix_achat_unitaire_ttc',
                'lr.prix_vente_unitaire_ttc',
                'lr.tva_pourcentage',
                'lr.coefficient_marge',
                'lr.marge_prevue_ttc',
                'f.nom as fournisseur'
            ])
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Historique des prix du produit',
            'data' => [
                'produit_id' => $id,
                'derniere_reception' => $derniereReception,
                'historique' => $historique,
                'tva_standard' => 18.00,
                'coefficient_marge_defaut' => $produit->coefficient_marge_defaut
            ]
        ]);
    }

    /**
     * Mettre à jour un produit
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $produit = Produit::find($id);

        if (!$produit) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé',
                'data' => null
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|string|max:50|unique:produits,code,' . $id,
            'nom' => 'sometimes|string|max:200',
            'code_barre' => 'nullable|string|max:50|unique:produits,code_barre,' . $id,
            'nom_commercial' => 'nullable|string|max:255',
            'molecule' => 'nullable|string|max:255',
            'fabricant_id' => 'nullable|exists:fabricants,id',
            'categorie_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'forme' => 'nullable|string|max:50',
            'dosage' => 'nullable|string|max:50',
            'prix_vente_conseille' => 'nullable|numeric|min:0',
            'coefficient_marge_defaut' => 'nullable|numeric|min:1',
            'commande_automatique' => 'nullable|boolean',
            'delai_livraison_jours' => 'nullable|integer|min:1',
            'unite_vente' => 'nullable|in:UNITE,BOITE,STRIP,FLACON',
            'unites_par_boite' => 'nullable|integer|min:1',
            'actif' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $produit->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Produit mis à jour avec succès',
            'data' => $produit
        ], 200);
    }

    /**
     * Supprimer un produit (soft delete via actif)
     */
    public function destroy(int $id): JsonResponse
    {
        $produit = Produit::find($id);

        if (!$produit) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé',
                'data' => null
            ], 404);
        }

        $produit->update(['actif' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Produit désactivé avec succès',
            'data' => null
        ], 200);
    }
}