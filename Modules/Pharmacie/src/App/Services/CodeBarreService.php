<?php

namespace Modules\Pharmacie\App\Services;

use Modules\Pharmacie\App\Models\Produit;
use Modules\Pharmacie\App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CodeBarreService
{
    /**
     * Scanner un code-barres et retourner les informations produit
     */
    public function scanner(string $codeBarre, int $depotId = null): array
    {
        // 1. Enregistrer historique scan
        $this->enregistrerScan($codeBarre);

        // 2. Rechercher produit par code-barres
        $produit = Produit::where('code_barre', $codeBarre)
            ->where('actif', true)
            ->first();

        if (!$produit) {
            return [
                'success' => false,
                'message' => "Code-barres non trouvé: $codeBarre",
                'code_barre' => $codeBarre,
            ];
        }

        // 3. Récupérer informations stock
        $stockInfo = $this->getStockInfo($produit->id, $depotId);

        return [
            'success' => true,
            'produit' => [
                'id' => $produit->id,
                'code' => $produit->code,
                'code_barre' => $produit->code_barre,
                'nom' => $produit->nom,
                'nom_commercial' => $produit->nom_commercial,
                'nom_complet' => $produit->nom_complet,
                'molecule' => $produit->molecule,
                'forme' => $produit->forme,
                'dosage' => $produit->dosage,
                'fabricant' => $produit->fabricant?->nom,
                'categorie' => $produit->categorie?->libelle,
            ],
            'stock' => $stockInfo,
            'prix' => [
                'dernier_prix_achat' => $produit->dernierPrixAchat(),
                'dernier_prix_vente' => $produit->dernierPrixVente(),
                'prix_achat_moyen' => $produit->prixAchatMoyen(),
            ],
        ];
    }

    /**
     * Rechercher produit par code-barres (sans enregistrer scan)
     */
    public function rechercherParCodeBarre(string $codeBarre): ?Produit
    {
        return Produit::where('code_barre', $codeBarre)
            ->where('actif', true)
            ->with(['fabricant', 'categorie'])
            ->first();
    }

    /**
     * Vérifier si un code-barres existe déjà
     */
    public function codeBarreExiste(string $codeBarre): bool
    {
        return Produit::where('code_barre', $codeBarre)->exists();
    }

    /**
     * Générer un code-barres interne (si produit sans code fabricant)
     */
    public function genererCodeBarreInterne(int $produitId): string
    {
        // Format: 999 (préfixe interne) + 9 chiffres (ID produit padded) + 1 checksum
        $prefix = '999';
        $productCode = str_pad($produitId, 9, '0', STR_PAD_LEFT);
        $checksum = $this->calculerChecksumEAN13($prefix . $productCode);

        return $prefix . $productCode . $checksum;
    }

    /**
     * Valider format EAN-13
     */
    public function validerEAN13(string $codeBarre): bool
    {
        // EAN-13 doit faire 13 chiffres
        if (!preg_match('/^\d{13}$/', $codeBarre)) {
            return false;
        }

        // Vérifier checksum
        $checksum = substr($codeBarre, -1);
        $calculatedChecksum = $this->calculerChecksumEAN13(substr($codeBarre, 0, 12));

        return $checksum == $calculatedChecksum;
    }

    /**
     * Calculer checksum EAN-13
     */
    private function calculerChecksumEAN13(string $code): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $code[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        $checksum = (10 - ($sum % 10)) % 10;
        return $checksum;
    }

    /**
     * Obtenir informations stock d'un produit
     */
    private function getStockInfo(int $produitId, ?int $depotId = null): array
    {
        if ($depotId) {
            // Stock dans un dépôt spécifique
            $stockTotal = Stock::where('produit_id', $produitId)
                ->where('depot_id', $depotId)
                ->where('quantite', '>', 0)
                ->where('date_peremption', '>=', now())
                ->sum('quantite');

            $lots = Stock::where('produit_id', $produitId)
                ->where('depot_id', $depotId)
                ->where('quantite', '>', 0)
                ->orderBy('date_peremption', 'asc')
                ->get(['numero_lot', 'quantite', 'date_peremption'])
                ->map(fn($s) => [
                    'lot' => $s->numero_lot,
                    'quantite' => $s->quantite,
                    'peremption' => $s->date_peremption->format('d/m/Y'),
                    'jours_avant_peremption' => now()->diffInDays($s->date_peremption),
                ]);

            return [
                'depot_id' => $depotId,
                'quantite_disponible' => $stockTotal,
                'lots' => $lots,
            ];
        } else {
            // Stock tous dépôts confondus
            $stockParDepot = Stock::where('produit_id', $produitId)
                ->where('quantite', '>', 0)
                ->where('date_peremption', '>=', now())
                ->with('depot:id,code,libelle')
                ->get()
                ->groupBy('depot_id')
                ->map(function ($stocks, $depotId) {
                    return [
                        'depot' => $stocks->first()->depot->code,
                        'depot_libelle' => $stocks->first()->depot->libelle,
                        'quantite' => $stocks->sum('quantite'),
                        'nb_lots' => $stocks->count(),
                    ];
                })
                ->values();

            $stockTotal = $stockParDepot->sum('quantite');

            return [
                'quantite_totale' => $stockTotal,
                'par_depot' => $stockParDepot,
            ];
        }
    }

    /**
     * Enregistrer historique de scan (pour analytics)
     */
    private function enregistrerScan(string $codeBarre): void
    {
        // Utiliser cache pour éviter trop d'écritures DB
        $key = "scan_" . now()->format('Y-m-d_H') . "_" . md5($codeBarre);

        Cache::increment($key);
        Cache::put($key . '_last', now(), now()->addDays(7));
    }

    /**
     * Obtenir statistiques de scan (top produits scannés)
     */
    public function getStatistiquesScan(int $jours = 7): array
    {
        $stats = [];

        // Récupérer clés de cache des X derniers jours
        for ($i = 0; $i < $jours; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');

            for ($hour = 0; $hour < 24; $hour++) {
                $pattern = "scan_{$date}_" . str_pad($hour, 2, '0', STR_PAD_LEFT) . "_*";
                
                // Note: Cette partie nécessiterait une vraie table scan_historique en production
                // Pour l'instant, c'est juste un exemple avec cache
            }
        }

        return $stats;
    }

    /**
     * Scanner multiple (pour inventaire rapide)
     */
    public function scannerMultiple(array $codeBarres, int $depotId): array
    {
        $resultats = [];

        foreach ($codeBarres as $codeBarre) {
            $resultats[] = $this->scanner($codeBarre, $depotId);
        }

        return [
            'total_scans' => count($codeBarres),
            'success' => count(array_filter($resultats, fn($r) => $r['success'])),
            'echecs' => count(array_filter($resultats, fn($r) => !$r['success'])),
            'details' => $resultats,
        ];
    }

    /**
     * Vérifier compatibilité code-barres pour vente
     */
    public function verifierDisponibilitePourVente(string $codeBarre, int $quantite, int $depotId): array
    {
        $produit = $this->rechercherParCodeBarre($codeBarre);

        if (!$produit) {
            return [
                'disponible' => false,
                'message' => "Produit non trouvé",
            ];
        }

        // Vérifier stock disponible
        $stockDisponible = Stock::where('produit_id', $produit->id)
            ->where('depot_id', $depotId)
            ->where('quantite', '>', 0)
            ->where('date_peremption', '>=', now())
            ->sum('quantite');

        if ($stockDisponible < $quantite) {
            return [
                'disponible' => false,
                'message' => "Stock insuffisant (disponible: $stockDisponible)",
                'stock_disponible' => $stockDisponible,
                'quantite_demandee' => $quantite,
            ];
        }

        return [
            'disponible' => true,
            'produit_id' => $produit->id,
            'nom' => $produit->nom,
            'stock_disponible' => $stockDisponible,
            'prix_vente' => $produit->dernierPrixVente(),
        ];
    }

    /**
     * Associer un code-barres à un produit existant
     */
    public function associerCodeBarre(int $produitId, string $codeBarre): bool
    {
        // Vérifier si code-barres déjà utilisé
        if ($this->codeBarreExiste($codeBarre)) {
            throw new \Exception("Ce code-barres est déjà utilisé par un autre produit");
        }

        // Valider format (optionnel)
        if (strlen($codeBarre) === 13 && !$this->validerEAN13($codeBarre)) {
            throw new \Exception("Code-barres EAN-13 invalide (checksum incorrect)");
        }

        $produit = Produit::findOrFail($produitId);
        $produit->update(['code_barre' => $codeBarre]);

        return true;
    }

    /**
     * Rechercher produits similaires par nom (si scan échoue)
     */
    public function rechercherSimilaires(string $recherche, int $limit = 10): array
    {
        return Produit::where('actif', true)
            ->where(function($q) use ($recherche) {
                $q->where('nom', 'LIKE', "%{$recherche}%")
                  ->orWhere('nom_commercial', 'LIKE', "%{$recherche}%")
                  ->orWhere('molecule', 'LIKE', "%{$recherche}%")
                  ->orWhere('code', 'LIKE', "%{$recherche}%");
            })
            ->with(['fabricant', 'categorie'])
            ->limit($limit)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'code_barre' => $p->code_barre,
                'nom' => $p->nom,
                'nom_complet' => $p->nom_complet,
                'fabricant' => $p->fabricant?->nom,
                'stock_total' => $p->stockTotal(),
            ])
            ->toArray();
    }
}