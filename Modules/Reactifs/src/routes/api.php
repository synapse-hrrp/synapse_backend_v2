<?php

use Illuminate\Support\Facades\Route;
use Modules\Reactifs\App\Http\Controllers\Api\ReactifController;
use Modules\Reactifs\App\Http\Controllers\Api\ReactifStockController;
use Modules\Reactifs\App\Http\Controllers\Api\ReactifCommandeController;
use Modules\Reactifs\App\Http\Controllers\Api\ReactifFournisseurController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    // ── Fournisseurs ───────────────────────────────────────────────────
    Route::apiResource('reactifs/fournisseurs', ReactifFournisseurController::class)
        ->names('reactifs.fournisseurs');
        
    // ── Commandes ──────────────────────────────────────────────────────
    Route::prefix('reactifs/commandes')->name('reactifs.commandes.')->group(function () {
        Route::get('/', [ReactifCommandeController::class, 'index'])->name('index');
        Route::post('/', [ReactifCommandeController::class, 'store'])->name('store');
        Route::get('/{commande}', [ReactifCommandeController::class, 'show'])->name('show');
        Route::post('/{commande}/envoyer', [ReactifCommandeController::class, 'envoyer'])->name('envoyer');
        Route::post('/{commande}/annuler', [ReactifCommandeController::class, 'annuler'])->name('annuler');
        Route::post('/lignes/{ligne}/receptionner', [ReactifCommandeController::class, 'receptionnerLigne'])->name('lignes.receptionner');
    });

    // ── Stock ──────────────────────────────────────────────────────────
    Route::prefix('reactifs/stock')->name('reactifs.stock.')->group(function () {
        Route::get('/', [ReactifStockController::class, 'index'])->name('index');
        Route::post('/entree', [ReactifStockController::class, 'entree'])->name('entree');
        Route::post('/sortie', [ReactifStockController::class, 'sortie'])->name('sortie');
        Route::post('/ajustement', [ReactifStockController::class, 'ajustement'])->name('ajustement');
        Route::get('/alertes', [ReactifStockController::class, 'alertes'])->name('alertes');
    });
   
    // ── Réactifs ───────────────────────────────────────────────────────
    Route::apiResource('reactifs', ReactifController::class)->names('reactifs');

    // Liaisons réactif <-> type d'examen
    Route::post('reactifs/{reactif}/examen-types', [ReactifController::class, 'lierExamenType'])->name('reactifs.lier-examen-type');
    Route::delete('reactifs/{reactif}/examen-types/{examenTypeId}', [ReactifController::class, 'delierExamenType'])->name('reactifs.delierExamenType');
    Route::get('reactifs/{reactif}/examen-types/{examenTypeId}',
    [ReactifController::class, 'getLiaisonExamenType'])->name('reactifs.get-liaison-examen-type');



});