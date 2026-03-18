<?php

namespace App\Console\Commands\Pharmacie;

use Illuminate\Console\Command;
use Modules\Pharmacie\App\Services\CommandeAutoService;

class VerifierStocksAuto extends Command
{
    protected $signature = 'pharmacie:verifier-stocks-auto
                            {--produit_id= : ID du produit (optionnel)}
                            {--depot_id= : ID du dépôt (optionnel)}';

    protected $description = 'Vérifier les stocks et déclencher commandes automatiques si nécessaire';

    public function handle(CommandeAutoService $service)
    {
        $this->info('🔄 Vérification des stocks...');

        if ($this->option('produit_id') && $this->option('depot_id')) {
            // Vérification spécifique
            $resultat = $service->verifierEtDeclencher(
                (int) $this->option('produit_id'),
                (int) $this->option('depot_id')
            );

            if ($resultat['commande_declenchee']) {
                $this->warn("⚠️  COMMANDE AUTO DÉCLENCHÉE : {$resultat['numero_commande']}");
                $this->newLine();
                $this->table(
                    ['Info', 'Valeur'],
                    [
                        ['Type', $resultat['type']],
                        ['Priorité', $resultat['priorite']],
                        ['Stock actuel', $resultat['stock_actuel']],
                        ['Seuil min', $resultat['seuil_min']],
                        ['Seuil max', $resultat['seuil_max']],
                        ['Quantité commandée', $resultat['quantite_commandee']],
                        ['Raison', $resultat['declencheur']],
                    ]
                );
            } else {
                $this->info("✅ Stock suffisant : {$resultat['raison']}");
            }
        } else {
            // Vérification globale
            $resultats = $service->verifierTousLesProduits();
            
            $this->info("✅ Vérification terminée");
            $this->info("📦 " . count($resultats) . " commande(s) automatique(s) déclenchée(s)");

            if (count($resultats) > 0) {
                $this->newLine();
                $this->table(
                    ['Commande', 'Type', 'Priorité', 'Quantité', 'Raison'],
                    array_map(fn($r) => [
                        $r['numero_commande'],
                        $r['type'],
                        $r['priorite'],
                        $r['quantite_commandee'],
                        \Illuminate\Support\Str::limit($r['declencheur'], 40),
                    ], $resultats)
                );
            }
        }

        return Command::SUCCESS;
    }
}