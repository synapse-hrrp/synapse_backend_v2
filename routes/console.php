<?php

use Illuminate\Support\Facades\Schedule;
use Modules\Pharmacie\App\Services\ConsommationAnalyseService;
use Modules\Pharmacie\App\Services\CommandeAutoService;

/*
|--------------------------------------------------------------------------
| Console Routes & Scheduled Tasks
|--------------------------------------------------------------------------
*/

// ========================================
// PHARMACIE - CONSOMMATIONS
// ========================================

Schedule::call(function () {
    $service = app(ConsommationAnalyseService::class);
    $service->analyserTous();
    \Log::info('[CRON] Consommations analysées');
})
->weekly()
->sundays()
->at('23:00')
->name('pharmacie:analyser-consommations');

// ========================================
// PHARMACIE - COMMANDES AUTOMATIQUES
// ========================================

Schedule::call(function () {
    $service = app(CommandeAutoService::class);
    $resultats = $service->verifierTousLesProduits();
    \Log::info('[CRON] Stocks vérifiés', ['commandes' => count($resultats)]);
})
->hourly()
->name('pharmacie:verifier-stocks-auto');

// ========================================
// PHARMACIE - ALERTES RUPTURES
// ========================================

Schedule::call(function () {
    $service = app(CommandeAutoService::class);
    $stats = $service->getStatistiques();
    
    if ($stats['urgentes_rupture'] > 0) {
        \Log::warning('[CRON] Ruptures détectées', [
            'nombre' => $stats['urgentes_rupture']
        ]);
    }
})
->everyFifteenMinutes()
->between('8:00', '18:00')
->weekdays()
->name('pharmacie:alerte-ruptures');

// ========================================
// PHARMACIE - NETTOYAGE EXPORTS
// ========================================

Schedule::call(function () {
    \Artisan::call('pharmacie:nettoyer-exports', ['--days' => 30]);
})
->daily()
->at('02:00')
->name('pharmacie:nettoyer-exports');

// ========================================
// PHARMACIE - ALERTES PÉREMPTION
// ========================================

Schedule::call(function () {
    $stocks = \Modules\Pharmacie\App\Models\Stock::query()
        ->with(['produit', 'depot'])
        ->where('quantite', '>', 0)
        ->whereBetween('date_peremption', [
            now()->toDateString(),
            now()->addDays(30)->toDateString()
        ])
        ->get();
    
    if ($stocks->count() > 0) {
        \Log::warning('[CRON] Stocks proches péremption', [
            'nombre' => $stocks->count()
        ]);
    }
})
->weekly()
->mondays()
->at('09:00')
->name('pharmacie:alerte-peremption');