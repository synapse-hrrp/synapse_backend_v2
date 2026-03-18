<?php

namespace App\Console\Commands\Pharmacie;

use Illuminate\Console\Command;
use Modules\Pharmacie\App\Services\ConsommationAnalyseService;

class AnalyserConsommations extends Command
{
    protected $signature = 'pharmacie:analyser-consommations
                            {--produit_id= : ID du produit (optionnel)}
                            {--depot_id= : ID du dépôt (optionnel)}';

    protected $description = 'Analyser les consommations et mettre à jour CMH/CMM';

    public function handle(ConsommationAnalyseService $service)
    {
        $this->info('🔄 Analyse des consommations...');

        if ($this->option('produit_id') && $this->option('depot_id')) {
            // Analyse spécifique
            $resultat = $service->analyserConsommation(
                (int) $this->option('produit_id'),
                (int) $this->option('depot_id')
            );

            $this->info('✅ Analyse terminée :');
            $this->table(
                ['Métrique', 'Valeur'],
                [
                    ['CMH (Consommation Moyenne Hebdo)', $resultat['cmh']],
                    ['CMM (Consommation Moyenne Mensuelle)', $resultat['cmm']],
                    ['Seuil Min Auto', $resultat['seuil_min_auto'] ?? 'N/A'],
                    ['Seuil Max Auto', $resultat['seuil_max_auto'] ?? 'N/A'],
                ]
            );
        } else {
            // Analyse globale
            $resultats = $service->analyserTous();
            
            $this->info("✅ " . count($resultats) . " produit(s) analysé(s)");
            
            if (count($resultats) > 0) {
                $this->newLine();
                foreach ($resultats as $r) {
                    $this->line("• {$r['produit_nom']} ({$r['depot_code']}) - CMH: {$r['analyse']['cmh']}");
                }
            } else {
                $this->warn('⚠️  Aucun produit avec commande automatique activée');
            }
        }

        return Command::SUCCESS;
    }
}