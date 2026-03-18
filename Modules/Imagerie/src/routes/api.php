<?php

use Illuminate\Support\Facades\Route;
use Modules\Imagerie\App\Http\Controllers\Api\ImagerieRequestController;
use Modules\Imagerie\App\Http\Controllers\Api\ImagerieController;
use Modules\Imagerie\App\Http\Controllers\Api\ImagerieTypeController;
use Modules\Imagerie\App\Http\Controllers\Api\ImagerieResultatController;

Route::middleware(['auth:sanctum'])->prefix('v1/imagerie')->group(function () {

    // ── Types d'imagerie ──────────────────────────────────────
    Route::apiResource('types', ImagerieTypeController::class)
        ->names('imagerie.types');

    // ── Demandes d'imagerie ───────────────────────────────────
    Route::prefix('imagerie-requests')->group(function () {
        Route::post('/', [ImagerieRequestController::class, 'store']);
        Route::get('worklist', [ImagerieRequestController::class, 'worklist']);
        Route::get('pending', [ImagerieRequestController::class, 'pending']);
    });

    // ── Examens d'imagerie ────────────────────────────────────
    Route::prefix('imageries')->group(function () {
        Route::post('/', [ImagerieController::class, 'store']);
        Route::put('{imagerie}/terminer', [ImagerieController::class, 'terminer']);
    });


    Route::prefix('resultats')->group(function () {
    Route::get('/', [ImagerieResultatController::class, 'index']);      // LIST
    Route::get('{resultat}', [ImagerieResultatController::class, 'show']); // DETAIL
    });

});
