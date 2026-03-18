<?php

use Illuminate\Support\Facades\Route;
use Modules\Soins\App\Http\Controllers\Api\ConsultationRequestController;
use Modules\Soins\App\Http\Controllers\Api\ConsultationController;
use Modules\Soins\App\Http\Controllers\Api\AccouchementRequestController;
use Modules\Soins\App\Http\Controllers\Api\AccouchementController;
use Modules\Soins\App\Http\Controllers\Api\HospitalisationRequestController;
use Modules\Soins\App\Http\Controllers\Api\HospitalisationController;
use Modules\Soins\App\Http\Controllers\Api\ActeOperatoireRequestController;
use Modules\Soins\App\Http\Controllers\Api\ActeOperatoireController;
use Modules\Soins\App\Http\Controllers\Api\PansementRequestController;
use Modules\Soins\App\Http\Controllers\Api\PansementController;
use Modules\Soins\App\Http\Controllers\Api\KinesitherapieRequestController;
use Modules\Soins\App\Http\Controllers\Api\KinesitherapieController;
use Modules\Soins\App\Http\Controllers\Api\DeclarationNaissanceController;

Route::middleware(['auth:sanctum'])->prefix('v1/soins')->group(function () {

    // ── Consultations ─────────────────────────────────────────
    Route::prefix('consultation-requests')->group(function () {
        Route::post('/', [ConsultationRequestController::class, 'store']);
        Route::get('worklist', [ConsultationRequestController::class, 'worklist']);
        Route::get('pending', [ConsultationRequestController::class, 'pending']);
    });

    Route::prefix('consultations')->group(function () {
        Route::post('/', [ConsultationController::class, 'store']);
        Route::put('{consultation}/terminer', [ConsultationController::class, 'terminer']);

        // ✅ Historique (ici !)
        Route::get('/', [ConsultationController::class, 'index']);
        Route::get('{consultation}', [ConsultationController::class, 'show']);
    });

    // ── Accouchements ─────────────────────────────────────────
    Route::prefix('accouchement-requests')->group(function () {
        Route::post('/', [AccouchementRequestController::class, 'store']);
        Route::get('worklist', [AccouchementRequestController::class, 'worklist']);
        Route::get('pending', [AccouchementRequestController::class, 'pending']);
    });

    Route::prefix('accouchements')->group(function () {
        // démarrer accouchement
        Route::post('/', [AccouchementController::class, 'store']);

        // terminer accouchement
        Route::put('{accouchement}/terminer', [AccouchementController::class, 'terminer']);

        // ✅ historique accouchements
        Route::get('/', [AccouchementController::class, 'index']);

        // ✅ détail accouchement
        Route::get('{accouchement}', [AccouchementController::class, 'show']);
    });

    // ──declarations-naissance  ──────────────────────────────────────
    Route::prefix('declarations-naissance')->group(function () {
        Route::post('/', [DeclarationNaissanceController::class, 'store']);        // create
        Route::get('{declarationNaissance}', [DeclarationNaissanceController::class, 'show']); // detail
        Route::put('{declarationNaissance}', [DeclarationNaissanceController::class, 'update']); // edit
    });

    // ── Hospitalisations ──────────────────────────────────────
    Route::prefix('hospitalisation-requests')->group(function () {
        Route::post('/', [HospitalisationRequestController::class, 'store']);
        Route::get('worklist', [HospitalisationRequestController::class, 'worklist']);
        Route::get('pending', [HospitalisationRequestController::class, 'pending']);
    });

    Route::prefix('hospitalisations')->group(function () {

        // démarrer hospitalisation
        Route::post('/', [HospitalisationController::class, 'store']);

        // enregistrer sortie
        Route::put('{hospitalisation}/sortie', [HospitalisationController::class, 'sortie']);

        // ✅ historique hospitalisations
        Route::get('/', [HospitalisationController::class, 'index']);

        // ✅ détail hospitalisation
        Route::get('{hospitalisation}', [HospitalisationController::class, 'show']);
    });

    // ── Actes Opératoires ─────────────────────────────────────
    Route::prefix('acte-operatoire-requests')->group(function () {
        Route::post('/', [ActeOperatoireRequestController::class, 'store']);
        Route::get('worklist', [ActeOperatoireRequestController::class, 'worklist']);
        Route::get('pending', [ActeOperatoireRequestController::class, 'pending']);
    });

    Route::prefix('actes-operatoires')->group(function () {
        // démarrer
        Route::post('/', [ActeOperatoireController::class, 'store']);

        // terminer
        Route::put('{acte}/terminer', [ActeOperatoireController::class, 'terminer']);

        // historique
        Route::get('/', [ActeOperatoireController::class, 'index']);

        // détail
        Route::get('{acte}', [ActeOperatoireController::class, 'show']);
    });


    // ── Pansement ──────────────────────────────────────────────────────────────
    Route::prefix('pansement-requests')->group(function () {
        Route::post('/', [PansementRequestController::class, 'store']);
        Route::get('worklist', [PansementRequestController::class, 'worklist']);
        Route::get('pending', [PansementRequestController::class, 'pending']);
    });

    Route::prefix('pansements')->group(function () {

        // démarrer un pansement
        Route::post('/', [PansementController::class, 'store']);

        // terminer un pansement
        Route::put('{pansement}/terminer', [PansementController::class, 'terminer']);

        // ✅ historique des pansements terminés
        Route::get('/', [PansementController::class, 'index']);

        // ✅ détail d'un pansement
        Route::get('{pansement}', [PansementController::class, 'show']);
    });

    // ── Kinésithérapie ─────────────────────────────────────────
    Route::prefix('kinesitherapie-requests')->group(function () {
        Route::post('/', [KinesitherapieRequestController::class, 'store']);
        Route::get('worklist', [KinesitherapieRequestController::class, 'worklist']);
        Route::get('pending', [KinesitherapieRequestController::class, 'pending']);
    });

    Route::prefix('kinesitherapies')->group(function () {

        // démarrer une séance
        Route::post('/', [KinesitherapieController::class, 'store']);

        // terminer la séance
        Route::put('{kinesitherapie}/terminer', [KinesitherapieController::class, 'terminer']);

        // ✅ historique
        Route::get('/', [KinesitherapieController::class, 'index']);

        // ✅ détail
        Route::get('{kinesitherapie}', [KinesitherapieController::class, 'show']);
    });
});