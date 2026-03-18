<?php

namespace Modules\Pharmacie\App\Services;

use Modules\Pharmacie\App\Interfaces\VenteInterface;
use Modules\Pharmacie\App\Repository\VenteRepository;
use Modules\Pharmacie\App\Repository\StockRepository;
use Modules\Pharmacie\App\Models\Produit;
use Modules\Pharmacie\App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class VenteService implements VenteInterface
{
    public function __construct(
        private VenteRepository $venteRepository,
        private StockRepository $stockRepository,
        private StockService $stockService
    ) {}

    public function creerVente(array $data): mixed
    {
        DB::beginTransaction();

        try {
            $numero = 'V-' . now()->format('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $vente = $this->venteRepository->creerVente([
                'numero' => $numero,
                'depot_id' => $data['depot_id'],
                'user_id' => Auth::id(),
                'date_vente' => now()->toDateString(),
                'type' => $data['type'] ?? 'VENTE',
                'statut' => 'EN_ATTENTE',
                'montant_ttc' => 0,
                'observations' => $data['observations'] ?? null,
            ]);

            $montantTotal = 0;

            foreach ($data['lignes'] as $ligneData) {
                $produit = Produit::findOrFail($ligneData['produit_id']);

                $prelevements = $this->stockService->preleverStock(
                    $produit->id,
                    $data['depot_id'],
                    $ligneData['quantite']
                );

                if (empty($prelevements)) {
                    throw new Exception("Stock insuffisant pour le produit '{$produit->nom}' dans le dépôt spécifié.");
                }

                $prixTotalPondere = 0;
                $quantiteTotale = 0;

                foreach ($prelevements as $prelevement) {
                    $stock = Stock::find($prelevement['stock_id']);
                    
                    if (!$stock) {
                        throw new Exception("Stock ID {$prelevement['stock_id']} introuvable.");
                    }

                    // ✅ Vérifier que le prix d'achat existe
                    if (!$stock->prix_achat_unitaire_ttc || $stock->prix_achat_unitaire_ttc <= 0) {
                        throw new Exception(
                            "Le stock ID {$stock->id} (lot {$stock->numero_lot}) n'a pas de prix d'achat. " .
                            "Ce stock est invalide et doit être corrigé ou supprimé."
                        );
                    }

                    $prixVente = $stock->prix_vente_unitaire_ttc;
                    
                    if (!$prixVente || $prixVente <= 0) {
                        $coefficientMarge = $produit->coefficient_marge_defaut ?? 1.40;
                        $prixVente = round($stock->prix_achat_unitaire_ttc * $coefficientMarge, 2);
                        
                        $tva = $stock->taux_tva ?? 18.00;
                        $prixVenteHt = round($prixVente / (1 + ($tva / 100)), 2);
                        $margeHt = round($prixVenteHt - $stock->prix_achat_unitaire_ht, 2);
                        $margeTtc = round($prixVente - $stock->prix_achat_unitaire_ttc, 2);
                        $tauxMarge = $stock->prix_achat_unitaire_ht > 0 
                            ? round(($margeHt / $stock->prix_achat_unitaire_ht) * 100, 2)
                            : 0;
                        
                        $stock->update([
                            'prix_vente_unitaire_ttc' => $prixVente,
                            'prix_vente_unitaire_ht' => $prixVenteHt,
                            'marge_unitaire_ht' => $margeHt,
                            'marge_unitaire_ttc' => $margeTtc,
                            'taux_marge' => $tauxMarge,
                        ]);
                        
                        Log::info("Prix de vente calculé automatiquement pour stock {$stock->id}: {$prixVente} FCFA");
                    }

                    $prixTotalPondere += $prixVente * $prelevement['quantite'];
                    $quantiteTotale += $prelevement['quantite'];
                }

                if ($prixTotalPondere == 0) {
                    throw new Exception(
                        "Impossible de calculer le prix de vente pour le produit '{$produit->nom}'. " .
                        "Aucun stock valide avec prix d'achat disponible."
                    );
                }

                $prixUnitaireTTC = round($prixTotalPondere / $quantiteTotale, 2);
                $montantLigne = round($prixUnitaireTTC * $ligneData['quantite'], 2);

                $ligneVente = $this->venteRepository->creerLigneVente([
                    'vente_id' => $vente->id,
                    'produit_id' => $produit->id,
                    'quantite' => $ligneData['quantite'],
                    'prix_unitaire_ttc' => $prixUnitaireTTC,
                    'montant_ligne_ttc' => $montantLigne,
                ]);

                foreach ($prelevements as $prelevement) {
                    $this->stockRepository->decrementerStock(
                        $prelevement['stock_id'],
                        $prelevement['quantite']
                    );

                    $this->venteRepository->creerMouvement([
                        'stock_id' => $prelevement['stock_id'],
                        'type' => $data['type'] === 'GRATUITE' ? 'SORTIE_GRATUITE' : 'SORTIE_VENTE',
                        'quantite' => -$prelevement['quantite'],
                        'vente_id' => $vente->id,
                        'user_id' => Auth::id(),
                    ]);

                    $this->venteRepository->creerLigneVenteStock([
                        'ligne_vente_id' => $ligneVente->id,
                        'stock_id' => $prelevement['stock_id'],
                        'quantite' => $prelevement['quantite'],
                    ]);
                }

                $montantTotal += $montantLigne;
            }

            $vente->update(['montant_ttc' => $montantTotal]);

            DB::commit();

            return $vente->load(['lignes.produit', 'depot', 'user']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function peutAnnuler(int $venteId): bool
    {
        $vente = $this->venteRepository->getVenteAvecLots($venteId);
        return $vente->statut === 'EN_ATTENTE';
    }

    public function annulerVente(int $venteId): bool
    {
        if (!$this->peutAnnuler($venteId)) {
            throw new Exception("Cette vente ne peut pas être annulée.");
        }

        DB::beginTransaction();

        try {
            $vente = $this->venteRepository->getVenteAvecLots($venteId);

            foreach ($vente->lignes as $ligne) {
                foreach ($ligne->lots as $lot) {
                    $this->stockRepository->incrementerStock($lot->stock_id, $lot->quantite);
                    $this->venteRepository->creerMouvement([
                        'stock_id' => $lot->stock_id,
                        'type' => 'ANNULATION_VENTE',
                        'quantite' => $lot->quantite,
                        'vente_id' => $vente->id,
                        'user_id' => Auth::id(),
                        'observations' => 'Annulation vente ' . $vente->numero,
                    ]);
                }
            }

            $this->venteRepository->annulerVente($venteId);
            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}