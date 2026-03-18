<?php

use Illuminate\Support\Facades\Route;
use Modules\Laboratoire\App\Http\Controllers\Api\ExamenRequestController;
use Modules\Laboratoire\App\Http\Controllers\Api\ExamenController;
use Modules\Laboratoire\App\Http\Controllers\Api\ExamenResultatController;
use Modules\Laboratoire\App\Http\Controllers\Api\ExamenTypeController;

Route::middleware(['auth:sanctum'])->prefix('v1/laboratoire')->group(function () {

    // ── Demandes d'examen ─────────────────────────────────────
    Route::prefix('examen-requests')->group(function () {
        Route::get('/',                  [ExamenRequestController::class, 'index']);
        Route::post('/',                 [ExamenRequestController::class, 'store']);
        Route::get('worklist',           [ExamenRequestController::class, 'worklist']);
        Route::get('pending',            [ExamenRequestController::class, 'pending']);
        Route::get('{examenRequest}',    [ExamenRequestController::class, 'show']);
        Route::put('{examenRequest}',    [ExamenRequestController::class, 'update']);
        Route::delete('{examenRequest}', [ExamenRequestController::class, 'destroy']);
    });

    // ── Types d'examens ───────────────────────────────────────
    Route::prefix('examen-types')->group(function () {
        Route::get('/',                          [ExamenTypeController::class, 'index']);
        Route::post('/',                         [ExamenTypeController::class, 'store']);
        Route::get('{examenType}',               [ExamenTypeController::class, 'show']);
        Route::put('{examenType}',               [ExamenTypeController::class, 'update']);
        Route::delete('{examenType}',            [ExamenTypeController::class, 'destroy']);
        Route::put('{examenType}/toggle-active', [ExamenTypeController::class, 'toggleActive']);
    });

    // ── Examens ───────────────────────────────────────────────
    Route::prefix('examens')->group(function () {
        Route::post('/',                  [ExamenController::class, 'store']);
        Route::put('{examen}/terminer',   [ExamenController::class, 'terminer']);
        Route::get('{examen}/resultats',  [ExamenResultatController::class, 'index']);
        Route::post('{examen}/resultats', [ExamenResultatController::class, 'store']);
    });

    // ── Résultats ─────────────────────────────────────────────
    Route::prefix('resultats')->group(function () {
        Route::get('/',             [ExamenResultatController::class, 'indexAll']);
        Route::get('{resultat}',    [ExamenResultatController::class, 'show']);
        Route::put('{resultat}',    [ExamenResultatController::class, 'update']);
        Route::delete('{resultat}', [ExamenResultatController::class, 'destroy']);
    });
});