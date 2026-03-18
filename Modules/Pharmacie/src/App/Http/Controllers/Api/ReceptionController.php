<?php

namespace Modules\Pharmacie\App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pharmacie\App\Interfaces\ReceptionInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

use Modules\Pharmacie\App\Models\Reception;

class ReceptionController extends Controller
{
    public function __construct(
        private ReceptionInterface $receptionService
    ) {}

    /**
     * Créer une réception (upsert stock + mouvements)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'commande_id' => 'nullable|exists:commandes,id',
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'depot_id' => 'nullable|exists:depots,id',
            'date_reception' => 'nullable|date',
            'observations' => 'nullable|string',
            'lignes' => 'required|array|min:1',
            'lignes.*.produit_id' => 'required|exists:produits,id',
            'lignes.*.depot_id' => 'nullable|exists:depots,id',
            'lignes.*.quantite' => 'required|integer|min:1',
            'lignes.*.numero_lot' => 'required|string|max:100',
            'lignes.*.date_peremption' => 'required|date|after:today',
            'lignes.*.date_fabrication' => 'nullable|date|before:today',
            'lignes.*.pays_origine' => 'nullable|string|max:100',
            'lignes.*.prix_achat' => 'required|numeric|min:0',
            'lignes.*.tva_applicable' => 'nullable|boolean',
            'lignes.*.tva_pourcentage' => 'nullable|numeric|min:0|max:100',
            'lignes.*.coefficient_marge' => 'nullable|numeric|min:1',
            'lignes.*.prix_vente_unitaire_ttc' => 'nullable|numeric|min:0',
            'lignes.*.ligne_commande_id' => 'nullable|exists:ligne_commandes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $reception = $this->receptionService->creerReception($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Réception créée avec succès',
                'data' => $reception
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
     * GET /api/v1/pharmacie/receptions
     * Filtres: fournisseur_id, depot_id, date, from/to, q, page, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) ($request->get('per_page', 20));
        if ($perPage <= 0 || $perPage > 100) $perPage = 20;

        $query = Reception::query()
            ->with([
                'fournisseur:id,nom,telephone,email',
                'depot:id,code,libelle',  // ✅ CORRIGÉ : libelle au lieu de nom
            ])
            ->withCount('lignes')
            ->orderByDesc('id');

        // ✅ Filtres
        if ($request->filled('fournisseur_id')) {
            $query->where('fournisseur_id', (int)$request->get('fournisseur_id'));
        }

        if ($request->filled('commande_id')) {
            $query->where('commande_id', (int)$request->get('commande_id'));
        }

        if ($request->filled('depot_id')) {
            $depotId = (int)$request->get('depot_id');
            $query->where(function($q) use ($depotId) {
                $q->where('depot_id', $depotId)
                  ->orWhereHas('lignes', fn($lq) => $lq->where('depot_id', $depotId));
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('date_reception', $request->get('date'));
        }

        if ($request->filled('from')) {
            $query->whereDate('date_reception', '>=', $request->get('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('date_reception', '<=', $request->get('to'));
        }

        if ($request->filled('q')) {
            $q = trim($request->get('q'));
            $query->where(function ($qq) use ($q) {
                $qq->where('numero', 'like', "%{$q}%")
                  ->orWhereHas('fournisseur', fn($fq) => $fq->where('nom', 'like', "%{$q}%"))
                  ->orWhereHas('lignes', function ($lq) use ($q) {
                      $lq->where('numero_lot', 'like', "%{$q}%")
                         ->orWhereHas('produit', fn($pq) => $pq->where('nom', 'like', "%{$q}%")
                                                          ->orWhere('code', 'like', "%{$q}%"));
                  });
            });
        }

        $p = $query->paginate($perPage);

        $items = $p->getCollection()->map(function ($rec) {
            $qt = (int) $rec->lignes()->sum('quantite');

            return [
                'id' => $rec->id,
                'numero' => $rec->numero,
                'commande_id' => $rec->commande_id,
                'fournisseur_id' => $rec->fournisseur_id,
                'depot_id' => $rec->depot_id,
                'date_reception' => $rec->date_reception,
                'statut' => $rec->statut ?? 'VALIDEE',
                'observations' => $rec->observations,
                'montant_total_ht' => $rec->montant_total_ht,
                'montant_total_ttc' => $rec->montant_total_ttc,
                'montant_total_vente' => $rec->montant_total_vente,
                'marge_totale_prevue' => $rec->marge_totale_prevue,
                'taux_marge_prevu' => $rec->taux_marge_prevu,
                'created_at' => $rec->created_at,
                'updated_at' => $rec->updated_at,
                'fournisseur' => $rec->fournisseur,
                'depot' => $rec->depot,
                'lignes_count' => (int)($rec->lignes_count ?? 0),
                'quantite_totale' => $qt,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Liste des réceptions',
            'data' => [
                'current_page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
                'last_page' => $p->lastPage(),
                'data' => $items,
            ],
        ]);
    }

    /**
     * GET /api/v1/pharmacie/receptions/{id}
     */
    public function show($id): JsonResponse
    {
        $rec = Reception::query()
            ->with([
                'fournisseur',
                'depot',  // ✅ Pas de select, charge toutes les colonnes
                'commande',
                'lignes.produit',
                'lignes.depot',  // ✅ Pas de select, charge toutes les colonnes
                'mouvements',
            ])
            ->find($id);

        if (!$rec) {
            return response()->json([
                'success' => false,
                'message' => 'Réception non trouvée',
                'data' => null,
            ], 404);
        }

        // ✅ Ajouter le taux de marge calculé
        $data = $rec->toArray();
        $data['taux_marge_prevu'] = $rec->taux_marge_prevu;

        return response()->json([
            'success' => true,
            'message' => 'Détail réception',
            'data' => $data,
        ]);
    }

    /**
     * POST /api/v1/pharmacie/receptions/{id}/annuler
     * ✅ SAFE : marque ANNULEE sans rollback stock.
     */
    public function annuler($id): JsonResponse
    {
        $rec = Reception::query()->find($id);

        if (!$rec) {
            return response()->json([
                'success' => false,
                'message' => 'Réception non trouvée',
                'data' => null,
            ], 404);
        }

        $statut = strtoupper((string)($rec->statut ?? 'VALIDEE'));
        if ($statut === 'ANNULEE') {
            return response()->json([
                'success' => true,
                'message' => 'Réception déjà annulée',
                'data' => $rec,
            ]);
        }

        DB::transaction(function () use ($rec) {
            $rec->statut = 'ANNULEE';
            $rec->save();
        });

        return response()->json([
            'success' => true,
            'message' => 'Réception annulée avec succès',
            'data' => null,
        ]);
    }
}