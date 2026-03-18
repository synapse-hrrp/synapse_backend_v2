<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Modules\Pharmacie\App\Services\ConsommationAnalyseService;
use Modules\Pharmacie\App\Services\CommandeAutoService;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ========================================
        // PHARMACIE - CONSOMMATIONS
        // ========================================
        
        /**
         * Enregistrer consommations hebdomadaires
         * Chaque dimanche à 23h00
         */
        $schedule->call(function () {
            $service = app(ConsommationAnalyseService::class);
            $service->analyserTous();
            
            \Log::info('[CRON] Consommations hebdomadaires analysées', [
                'date' => now()->toDateTimeString()
            ]);
        })
        ->weekly()
        ->sundays()
        ->at('23:00')
        ->name('pharmacie:analyser-consommations')
        ->withoutOverlapping()
        ->onOneServer();

        // ========================================
        // PHARMACIE - COMMANDES AUTOMATIQUES
        // ========================================
        
        /**
         * Vérifier stocks et déclencher commandes auto
         * Toutes les heures
         */
        $schedule->call(function () {
            $service = app(CommandeAutoService::class);
            $resultats = $service->verifierTousLesProduits();
            
            \Log::info('[CRON] Vérification stocks et commandes auto', [
                'commandes_declenchees' => count($resultats),
                'date' => now()->toDateTimeString()
            ]);
        })
        ->hourly()
        ->name('pharmacie:verifier-stocks-auto')
        ->withoutOverlapping()
        ->onOneServer();

        /**
         * Vérification renforcée stocks critiques
         * Toutes les 15 minutes pendant heures ouvrables (8h-18h)
         */
        $schedule->call(function () {
            $service = app(CommandeAutoService::class);
            $stats = $service->getStatistiques();
            
            if ($stats['urgentes_rupture'] > 0) {
                \Log::warning('[CRON] ALERTE : Ruptures de stock détectées', [
                    'nombre_urgentes' => $stats['urgentes_rupture'],
                    'date' => now()->toDateTimeString()
                ]);
            }
        })
        ->everyFifteenMinutes()
        ->between('8:00', '18:00')
        ->weekdays()
        ->name('pharmacie:alerte-ruptures')
        ->withoutOverlapping()
        ->onOneServer();

        // ========================================
        // PHARMACIE - NETTOYAGE & MAINTENANCE
        // ========================================
        
        /**
         * Nettoyer exports anciens (> 30 jours)
         * Tous les jours à 2h00
         */
        $schedule->call(function () {
            \Artisan::call('pharmacie:nettoyer-exports', ['--days' => 30]);
            
            \Log::info('[CRON] Nettoyage exports anciens terminé', [
                'date' => now()->toDateTimeString()
            ]);
        })
        ->daily()
        ->at('02:00')
        ->name('pharmacie:nettoyer-exports')
        ->onOneServer();

        /**
         * Notification stocks proches péremption (< 30 jours)
         * Tous les lundis à 9h00
         */
        $schedule->call(function () {
            $stocks = \Modules\Pharmacie\App\Models\Stock::query()
                ->with(['produit', 'depot'])
                ->where('quantite', '>', 0)
                ->whereBetween('date_peremption', [
                    now()->toDateString(),
                    now()->addDays(30)->toDateString()
                ])
                ->get();
            
            if ($stocks->count() > 0) {
                \Log::warning('[CRON] Stocks proches péremption détectés', [
                    'nombre_lots' => $stocks->count(),
                    'date' => now()->toDateTimeString()
                ]);
            }
        })
        ->weekly()
        ->mondays()
        ->at('09:00')
        ->name('pharmacie:alerte-peremption')
        ->onOneServer();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        $this->load(__DIR__.'/Commands/Pharmacie');

        require base_path('routes/console.php');
    }
}