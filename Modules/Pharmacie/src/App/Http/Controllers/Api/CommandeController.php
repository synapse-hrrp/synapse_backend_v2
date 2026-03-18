<?php

namespace Modules\Pharmacie\App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pharmacie\App\Models\Commande;
use Modules\Pharmacie\App\Models\LigneCommande;
use Illuminate\Support\Facades\Validator;

class CommandeController extends Controller
{
    /**
     * Liste des commandes
     */
    public function index(): JsonResponse
    {
        $commandes = Commande::with(['fournisseur', 'lignes.produit'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste des commandes',
            'data' => $commandes
        ], 200);
    }

    /**
     * Créer une commande
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'date_commande' => 'required|date',
            'observations' => 'nullable|string',
            'lignes' => 'required|array|min:1',
            'lignes.*.produit_id' => 'required|exists:produits,id',
            'lignes.*.quantite_commandee' => 'required|integer|min:1',
            'lignes.*.prix_unitaire' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Générer numéro
        $numero = 'C-' . now()->format('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $commande = Commande::create([
            'numero' => $numero,
            'fournisseur_id' => $request->fournisseur_id,
            'date_commande' => $request->date_commande,
            'observations' => $request->observations,
        ]);

        // Créer lignes
        foreach ($request->lignes as $ligne) {
            LigneCommande::create([
                'commande_id' => $commande->id,
                'produit_id' => $ligne['produit_id'],
                'quantite_commandee' => $ligne['quantite_commandee'],
                'prix_unitaire' => $ligne['prix_unitaire'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Commande créée avec succès',
            'data' => $commande->load(['lignes.produit', 'fournisseur'])
        ], 201);
    }

    /**
     * Afficher une commande
     */
    public function show(int $id): JsonResponse
    {
        $commande = Commande::with(['fournisseur', 'lignes.produit'])->find($id);

        if (!$commande) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Détails de la commande',
            'data' => $commande
        ], 200);
    }

    /**
     * Ajouter une ligne à une commande existante
     */
    public function ajouterLigne(Request $request, int $id): JsonResponse
    {
        $commande = Commande::find($id);

        if (!$commande) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
                'data' => null
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'produit_id' => 'required|exists:produits,id',
            'quantite_commandee' => 'required|integer|min:1',
            'prix_unitaire' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $ligne = LigneCommande::create([
            'commande_id' => $commande->id,
            'produit_id' => $request->produit_id,
            'quantite_commandee' => $request->quantite_commandee,
            'prix_unitaire' => $request->prix_unitaire,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ligne ajoutée avec succès',
            'data' => $ligne->load('produit')
        ], 201);
    }
}