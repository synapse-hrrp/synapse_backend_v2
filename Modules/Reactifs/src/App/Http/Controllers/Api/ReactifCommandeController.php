<?php

namespace Modules\Reactifs\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Reactifs\App\Models\Reactif;
use Modules\Reactifs\App\Models\ReactifCommande;
use Modules\Reactifs\App\Models\ReactifCommandeLigne;
use Modules\Reactifs\App\Models\ReactifFournisseur;
use Modules\Reactifs\App\Services\ReactifStockService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ReactifCommandeController extends Controller
{
    public function __construct(
        private ReactifStockService $stockService
    ) {}

    public function index(): JsonResponse
    {
        $commandes = ReactifCommande::with('fournisseur')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $commandes,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fournisseur_id'         => 'required|exists:reactif_fournisseurs,id',
            'date_commande'          => 'required|date',
            'date_livraison_prevue'  => 'nullable|date|after_or_equal:date_commande',
            'notes'                  => 'nullable|string',
            'lignes'                 => 'required|array|min:1',
            'lignes.*.reactif_id'    => 'required|exists:reactifs,id',
            'lignes.*.quantite'      => 'required|numeric|min:0.001',
            'lignes.*.prix_unitaire' => 'nullable|numeric|min:0',
        ]);

        $commande = ReactifCommande::create([
            'numero'                => 'CMD-' . strtoupper(Str::random(8)),
            'fournisseur_id'        => $validated['fournisseur_id'],
            'statut'                => 'brouillon',
            'date_commande'         => $validated['date_commande'],
            'date_livraison_prevue' => $validated['date_livraison_prevue'] ?? null,
            'notes'                 => $validated['notes'] ?? null,
            'created_by'            => auth()->id(),
        ]);

        foreach ($validated['lignes'] as $ligne) {
            $prixUnitaire = $ligne['prix_unitaire'] ?? 0;
            ReactifCommandeLigne::create([
                'commande_id'        => $commande->id,
                'reactif_id'         => $ligne['reactif_id'],
                'quantite_commandee' => $ligne['quantite'],
                'quantite_recue'     => 0,
                'prix_unitaire'      => $prixUnitaire,
                'montant_ligne'      => $ligne['quantite'] * $prixUnitaire,
                'statut'             => 'en_attente',
            ]);
        }

        $commande->recalculerMontant();
        $commande->load(['fournisseur', 'lignes.reactif']);

        return response()->json([
            'message' => "Commande {$commande->numero} créée.",
            'data'    => $commande,
        ], 201);
    }

    public function show(ReactifCommande $commande): JsonResponse
    {
        $commande->load(['fournisseur', 'lignes.reactif']);

        return response()->json([
            'data' => $commande,
        ]);
    }

    public function envoyer(ReactifCommande $commande): JsonResponse
    {
        $commande->update(['statut' => 'envoyee']);

        return response()->json([
            'message' => 'Commande marquée comme envoyée.',
            'data'    => $commande,
        ]);
    }

    public function receptionnerLigne(Request $request, ReactifCommandeLigne $ligne): JsonResponse
    {
        $validated = $request->validate([
            'quantite_recue'  => 'required|numeric|min:0.001',
            'date_peremption' => 'nullable|date',
            'numero_lot'      => 'nullable|string|max:100',
        ]);

        $ligne->update([
            'date_peremption' => $validated['date_peremption'] ?? null,
            'numero_lot'      => $validated['numero_lot'] ?? null,
        ]);

        $mouvement = $this->stockService->receptionnerLigne($ligne, $validated['quantite_recue']);

        return response()->json([
            'message'  => "Réception de {$validated['quantite_recue']} enregistrée.",
            'data'     => $mouvement,
            'stock_actuel' => $ligne->reactif->fresh()->stock_actuel,
        ]);
    }

    public function annuler(ReactifCommande $commande): JsonResponse
    {
        $commande->update(['statut' => 'annulee']);

        return response()->json([
            'message' => 'Commande annulée.',
            'data'    => $commande,
        ]);
    }
}