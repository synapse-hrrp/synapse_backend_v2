<?php

namespace Modules\Pharmacie\App\Services;

use Modules\Pharmacie\App\Interfaces\ReceptionInterface;
use Modules\Pharmacie\App\Repository\ReceptionRepository;
use Modules\Pharmacie\App\Repository\StockRepository;
use Modules\Pharmacie\App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class ReceptionService implements ReceptionInterface
{
    public function __construct(
        private ReceptionRepository $receptionRepository,
        private StockRepository $stockRepository,
        private StockService $stockService
    ) {}

    /**
     * Créer réception + upsert stock
     *
     * Format data :
     * [
     *   'commande_id' => 1, // nullable
     *   'fournisseur_id' => 1,
     *   'depot_id' => 1, // nullable
     *   'observations' => '...',
     *   'lignes' => [
     *     [
     *       'produit_id' => 1,
     *       'depot_id' => 1,
     *       'quantite' => 100,
     *       'numero_lot' => 'LOT123',
     *       'date_peremption' => '2026-12-31',
     *       'prix_achat' => 500,
     *       'coefficient_marge' => 1.3, // optionnel, défaut 1.3
     *       'prix_vente_unitaire_ttc' => 650, // optionnel
     *     ],
     *     ...
     *   ]
     * ]
     */
    public function creerReception(array $data): mixed
    {
        DB::beginTransaction();

        try {
            // 1. Générer numéro réception
            $numero = 'R-' . now()->format('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // 2. Créer réception (SANS les totaux pour l'instant)
            $reception = $this->receptionRepository->creerReception([
                'numero'         => $numero,
                'commande_id'    => $data['commande_id'] ?? null,
                'fournisseur_id' => $data['fournisseur_id'],
                'depot_id'       => $data['depot_id'] ?? null,
                'date_reception' => $data['date_reception'] ?? now()->toDateString(),
                'observations'   => $data['observations'] ?? null,
                'statut'         => 'VALIDEE',
            ]);

            // 3. Traiter chaque ligne
            foreach ($data['lignes'] as $ligneData) {

                // 3.1 Créer ligne réception
                $ligneReception = $this->receptionRepository->creerLigneReception([
                    'reception_id'            => $reception->id,
                    'produit_id'              => $ligneData['produit_id'],
                    'depot_id'                => $ligneData['depot_id'] ?? $reception->depot_id,
                    'quantite'                => $ligneData['quantite'],
                    'numero_lot'              => $ligneData['numero_lot'],
                    'date_peremption'         => $ligneData['date_peremption'],
                    'date_fabrication'        => $ligneData['date_fabrication'] ?? null,
                    'pays_origine'            => $ligneData['pays_origine'] ?? null,
                    'prix_achat_unitaire_ht'  => $ligneData['prix_achat'],
                    'tva_applicable'          => $ligneData['tva_applicable'] ?? true,
                    'tva_pourcentage'         => $ligneData['tva_pourcentage'] ?? 18.00,
                    'coefficient_marge'       => $ligneData['coefficient_marge'] ?? null,
                    'prix_vente_unitaire'     => $ligneData['prix_vente_unitaire_ttc'] ?? null,
                ]);
                // ✅ Les autres champs (prix_vente, marges, totaux) sont calculés automatiquement par boot() saving

                // 3.2 ✅ Upsert stock avec TOUTES les nouvelles colonnes
                $stock = $this->stockRepository->upsertStock([
                    'produit_id'      => $ligneReception->produit_id,
                    'depot_id'        => $ligneReception->depot_id,
                    'numero_lot'      => $ligneReception->numero_lot,
                    'date_peremption' => $ligneReception->date_peremption,
                    'date_fabrication' => $ligneReception->date_fabrication,
                    'pays_origine'    => $ligneReception->pays_origine,
                    'quantite'        => $ligneReception->quantite,
                    
                    // Ancienne colonne (compatibilité)
                    'prix_achat'      => $ligneReception->prix_achat_unitaire_ht,
                    
                    // ✅ NOUVELLES COLONNES : Copier depuis ligne_receptions
                    'prix_achat_unitaire_ht'  => $ligneReception->prix_achat_unitaire_ht,
                    'prix_achat_unitaire_ttc' => $ligneReception->prix_achat_unitaire_ttc,
                    'taux_tva'                => $ligneReception->tva_pourcentage,
                    'montant_tva_unitaire'    => $ligneReception->montant_tva_unitaire,
                    
                    'prix_vente_unitaire_ht'  => $ligneReception->prix_vente_unitaire_ht,
                    'prix_vente_unitaire_ttc' => $ligneReception->prix_vente_unitaire_ttc,
                    'marge_unitaire_ht'       => $ligneReception->marge_prevue_ht,
                    'marge_unitaire_ttc'      => $ligneReception->marge_prevue_ttc,
                    'taux_marge'              => $ligneReception->taux_marge_prevu,
                ]);

                // 3.3 Créer mouvement ENTREE
                DB::table('mouvement_stocks')->insert([
                    'stock_id'     => $stock->id,
                    'type'         => 'ENTREE',
                    'quantite'     => $ligneReception->quantite,
                    'reception_id' => $reception->id,
                    'user_id'      => Auth::id(),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);

                // 3.4 Mettre à jour ligne_commande si existe
                if (isset($data['commande_id']) && isset($ligneData['ligne_commande_id'])) {
                    $this->receptionRepository->mettreAJourQuantiteRecue(
                        $ligneData['ligne_commande_id'],
                        $ligneData['quantite']
                    );
                }
            }

            // ✅ 4. CALCULER LES TOTAUX DE LA RÉCEPTION (achat + vente + marge)
            $reception->calculerMontants();

            // 5. Mettre à jour statut commande si liée
            if (isset($data['commande_id'])) {
                $this->mettreAJourCommande($data['commande_id']);
            }

            DB::commit();

            // 6. Charger les relations
            return $reception->load(['lignes.produit', 'lignes.depot', 'fournisseur', 'depot']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mettre à jour statut commande
     */
    public function mettreAJourCommande(int $commandeId): void
    {
        $this->receptionRepository->mettreAJourStatutCommande($commandeId);
    }
}