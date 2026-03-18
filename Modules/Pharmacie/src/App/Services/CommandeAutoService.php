<?php

namespace Modules\Pharmacie\App\Services;

use Modules\Pharmacie\App\Models\Produit;
use Modules\Pharmacie\App\Models\Depot;
use Modules\Pharmacie\App\Models\Commande;
use Modules\Pharmacie\App\Models\LigneCommande;
use Modules\Pharmacie\App\Models\SeuilStock;
use Modules\Pharmacie\App\Models\Stock;
use Modules\Pharmacie\App\Models\Fournisseur;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CommandeAutoService
{
    public function __construct(
        private ConsommationAnalyseService $consommationService
    ) {}

    /**
     * Vérifier tous les produits et déclencher commandes auto si nécessaire
     * (Appelé par Cron toutes les heures)
     */
    public function verifierTousLesProduits(): array
    {
        $commandesCreees = [];

        // Récupérer produits avec commande auto activée
        $produits = Produit::commandeAuto()->get();

        foreach ($produits as $produit) {
            $depots = Depot::actif()->get();

            foreach ($depots as $depot) {
                try {
                    $resultat = $this->verifierEtDeclencher($produit->id, $depot->id);
                    
                    if ($resultat['commande_declenchee']) {
                        $commandesCreees[] = $resultat;
                    }
                } catch (Exception $e) {
                    Log::error("Erreur vérification commande auto", [
                        'produit_id' => $produit->id,
                        'depot_id' => $depot->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $commandesCreees;
    }

    /**
     * Vérifier un produit dans un dépôt et déclencher commande si nécessaire
     */
    public function verifierEtDeclencher(int $produitId, int $depotId): array
    {
        $produit = Produit::findOrFail($produitId);
        
        // ✅ CORRECTION 1: Vérifier commande_automatique activée
        if (!$produit->commande_automatique) {
            return [
                'commande_declenchee' => false,
                'raison' => 'Commande automatique non activée pour ce produit',
            ];
        }

        // Récupérer seuil
        $seuil = SeuilStock::where('produit_id', $produitId)
            ->where('depot_id', $depotId)
            ->first();

        if (!$seuil) {
            return [
                'commande_declenchee' => false,
                'raison' => 'Aucun seuil défini pour ce produit dans ce dépôt',
            ];
        }

        // Calculer stock actuel (non périmé)
        $stockActuel = Stock::where('produit_id', $produitId)
            ->where('depot_id', $depotId)
            ->where('quantite', '>', 0)
            ->where('date_peremption', '>=', now()->toDateString())
            ->sum('quantite');

        // ✅ CORRECTION 2: Déterminer type AVANT de vérifier doublon
        $type = null;
        $priorite = null;
        $declencheur = null;

        // CAS 1 : Rupture de stock (priorité CRITIQUE)
        if ($stockActuel == 0) {
            $type = 'URGENCE_RUPTURE';
            $priorite = 'CRITIQUE';
            $declencheur = 'Rupture de stock';
        }
        // CAS 2 : Stock <= seuil_min (priorité URGENTE)
        elseif ($stockActuel <= $seuil->seuil_min_actif) {
            $type = 'AUTO_SEUIL_MIN';
            $priorite = 'URGENTE';
            $declencheur = "Stock actuel ({$stockActuel}) <= seuil min ({$seuil->seuil_min_actif})";
        }
        // CAS 3 : Surconsommation détectée (priorité NORMALE)
        elseif ($this->consommationService->detecterSurconsommation($produitId, $depotId)) {
            $type = 'AUTO_SURCONSO';
            $priorite = 'NORMALE';
            $pourcentage = $seuil->seuil_alerte_surconsommation * 100;
            $declencheur = "Surconsommation détectée (>{$pourcentage}% de la CMH)";
        }
        // Aucun cas détecté
        else {
            return [
                'commande_declenchee' => false,
                'raison' => 'Stock suffisant (au-dessus du seuil minimum)',
                'stock_actuel' => $stockActuel,
                'seuil_min' => $seuil->seuil_min_actif,
                'seuil_max' => $seuil->seuil_max_actif,
            ];
        }

        // ✅ CORRECTION 3: Calculer quantité AVANT de vérifier doublon
        $quantiteACommander = $this->calculerQuantiteACommander($stockActuel, $seuil);

        // ✅ CORRECTION 4: Si quantité <= 0, forcer au moins seuil_max
        if ($quantiteACommander <= 0) {
            $quantiteACommander = $seuil->seuil_max_actif > 0 ? $seuil->seuil_max_actif : 100;
        }

        // ✅ CORRECTION 5: Vérifier si commande récente existe
        $commandeRecente = Commande::where('type', $type)
            ->whereHas('lignes', function($q) use ($produit) {
                $q->where('produit_id', $produit->id);
            })
            ->whereIn('statut', ['BROUILLON', 'EN_ATTENTE_VALIDATION', 'VALIDEE'])
            ->where('created_at', '>', now()->subHours(24))
            ->first();

        if ($commandeRecente) {
            return [
                'commande_declenchee' => false,
                'raison' => "Commande similaire déjà créée il y a moins de 24h (#{$commandeRecente->numero})",
                'commande_existante_id' => $commandeRecente->id,
            ];
        }

        // Déclencher commande
        return $this->declencherCommandeAuto(
            $produit,
            $depotId,
            $type,
            $declencheur,
            $priorite,
            $stockActuel,
            $seuil,
            $quantiteACommander
        );
    }

    /**
     * Déclencher commande automatique
     */
    private function declencherCommandeAuto(
        Produit $produit,
        int $depotId,
        string $type,
        string $declencheur,
        string $priorite,
        int $stockActuel,
        SeuilStock $seuil,
        int $quantiteACommander
    ): array {
        DB::beginTransaction();

        try {
            // 1. Trouver fournisseur par défaut (le premier actif)
            $fournisseur = Fournisseur::where('actif', true)->first();

            if (!$fournisseur) {
                throw new Exception("Aucun fournisseur actif trouvé");
            }

            // 2. Générer numéro commande
            $numero = 'CMD-AUTO-' . now()->format('Ymd-His') . '-' . $produit->id;

            // 3. Créer commande
            $commande = Commande::create([
                'numero' => $numero,
                'type' => $type,
                'declencheur' => $declencheur,
                'priorite' => $priorite,
                'stock_actuel_declenchement' => $stockActuel,
                'cmh_au_declenchement' => $seuil->cmh_actuelle,
                'fournisseur_id' => $fournisseur->id,
                'depot_id' => $depotId,
                'date_commande' => now()->toDateString(),
                'date_livraison_prevue' => now()->addDays($produit->delai_livraison_jours)->toDateString(),
                'statut' => 'EN_ATTENTE_VALIDATION',
                'observations' => "Commande automatique - $declencheur",
            ]);

            // 4. Créer ligne commande
            $ligneCommande = LigneCommande::create([
                'commande_id' => $commande->id,
                'produit_id' => $produit->id,
                'quantite_commandee' => $quantiteACommander,
                'quantite_recue' => 0,
                'prix_unitaire' => $produit->prixAchatMoyen() ?? 0,
                'stock_actuel' => $stockActuel,
                'cmh' => $seuil->cmh_actuelle,
                'seuil_max' => $seuil->seuil_max_actif,
                'seuil_min' => $seuil->seuil_min_actif,
                'raison_commande' => $declencheur,
            ]);

            // 5. Mettre à jour derniere_commande_auto_at
            $produit->update([
                'derniere_commande_auto_at' => now(),
            ]);

            DB::commit();

            return [
                'commande_declenchee' => true,
                'commande_id' => $commande->id,
                'numero_commande' => $commande->numero,
                'type' => $type,
                'priorite' => $priorite,
                'quantite_commandee' => $quantiteACommander,
                'stock_actuel' => $stockActuel,
                'seuil_min' => $seuil->seuil_min_actif,
                'seuil_max' => $seuil->seuil_max_actif,
                'declencheur' => $declencheur,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calculer quantité optimale à commander
     */
    private function calculerQuantiteACommander(int $stockActuel, SeuilStock $seuil): int
    {
        // Quantité = seuil_max - stock_actuel
        $seuilMax = $seuil->seuil_max_actif;
        
        // Si pas de seuil max défini, utiliser valeur par défaut
        if ($seuilMax <= 0) {
            $seuilMax = 100;
        }
        
        $quantite = $seuilMax - $stockActuel;

        return max(0, $quantite);
    }

    /**
     * Valider une commande automatique (passer de EN_ATTENTE_VALIDATION à VALIDEE)
     */
    public function validerCommandeAuto(int $commandeId, int $userId): bool
    {
        $commande = Commande::findOrFail($commandeId);

        if (!in_array($commande->type, ['AUTO_SEUIL_MIN', 'AUTO_SURCONSO', 'URGENCE_RUPTURE'])) {
            throw new Exception("Cette commande n'est pas une commande automatique");
        }

        if ($commande->statut !== 'EN_ATTENTE_VALIDATION') {
            throw new Exception("Cette commande n'est pas en attente de validation");
        }

        $commande->update([
            'statut' => 'VALIDEE',
            'validee_par_user_id' => $userId,
            'validee_at' => now(),
        ]);

        return true;
    }

    /**
     * Récupérer toutes les commandes auto en attente de validation
     */
    public function getCommandesEnAttenteValidation()
    {
        return Commande::whereIn('type', ['AUTO_SEUIL_MIN', 'AUTO_SURCONSO', 'URGENCE_RUPTURE'])
            ->where('statut', 'EN_ATTENTE_VALIDATION')
            ->with(['lignes.produit', 'fournisseur', 'depot'])
            ->orderByRaw("FIELD(priorite, 'CRITIQUE', 'URGENTE', 'NORMALE')")
            ->orderBy('created_at', 'ASC')
            ->get();
    }

    /**
     * Obtenir statistiques commandes auto
     */
    public function getStatistiques(): array
    {
        $total = Commande::whereIn('type', ['AUTO_SEUIL_MIN', 'AUTO_SURCONSO', 'URGENCE_RUPTURE'])->count();
        
        $enAttente = Commande::whereIn('type', ['AUTO_SEUIL_MIN', 'AUTO_SURCONSO', 'URGENCE_RUPTURE'])
            ->where('statut', 'EN_ATTENTE_VALIDATION')
            ->count();

        $validees = Commande::whereIn('type', ['AUTO_SEUIL_MIN', 'AUTO_SURCONSO', 'URGENCE_RUPTURE'])
            ->where('statut', 'VALIDEE')
            ->count();

        $urgentes = Commande::where('type', 'URGENCE_RUPTURE')
            ->where('statut', 'EN_ATTENTE_VALIDATION')
            ->count();

        $derniere24h = Commande::whereIn('type', ['AUTO_SEUIL_MIN', 'AUTO_SURCONSO', 'URGENCE_RUPTURE'])
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        return [
            'total_commandes_auto' => $total,
            'en_attente_validation' => $enAttente,
            'validees' => $validees,
            'urgentes_rupture' => $urgentes,
            'derniere_24h' => $derniere24h,
        ];
    }
}
