<?php

namespace Modules\Pharmacie\App\Console;

use Illuminate\Console\Command;
use Modules\Pharmacie\App\Interfaces\StockInterface;
use Modules\Pharmacie\App\Notifications\StockPeremptionProche;
use Modules\Pharmacie\App\Notifications\StockPerime;
use App\Models\User;

class EnvoyerAlertesPeremption extends Command
{
    protected $signature = 'pharmacie:alertes-peremption';
    protected $description = 'Envoyer des emails d\'alerte pour les stocks proches de la péremption';

    public function __construct(
        private StockInterface $stockService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('🔍 Vérification des stocks...');

        // Récupérer les stocks proches de la péremption
        $stocksProches = $this->stockService->getStocksProches(30);
        
        // Récupérer les stocks périmés
        $stocksPerimes = $this->stockService->getStocksPerimes();

        // Récupérer les administrateurs/gestionnaires
        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', 'Admin'); // Adaptez selon votre système de rôles
        })->get();

        // Envoyer alerte péremption proche
        if ($stocksProches->count() > 0) {
            $this->warn('⚠️  ' . $stocksProches->count() . ' lot(s) proche(s) de la péremption');
            
            foreach ($admins as $admin) {
                $admin->notify(new StockPeremptionProche($stocksProches));
            }
            
            $this->info('✅ Notifications envoyées à ' . $admins->count() . ' utilisateur(s)');
        } else {
            $this->info('✅ Aucun stock proche de la péremption');
        }

        // Envoyer alerte stocks périmés
        if ($stocksPerimes->count() > 0) {
            $this->error('🚨 ' . $stocksPerimes->count() . ' lot(s) PÉRIMÉ(S) !');
            
            foreach ($admins as $admin) {
                $admin->notify(new StockPerime($stocksPerimes));
            }
            
            $this->info('✅ Alertes urgentes envoyées');
        } else {
            $this->info('✅ Aucun stock périmé');
        }

        $this->info('✨ Vérification terminée');
        
        return Command::SUCCESS;
    }
}