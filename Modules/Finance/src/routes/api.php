<?php

use Illuminate\Support\Facades\Route;

use Modules\Finance\App\Http\Controllers\SessionController;
use Modules\Finance\App\Http\Controllers\PaymentController;
use Modules\Finance\App\Http\Controllers\AuditController;
use Modules\Finance\App\Http\Controllers\FactureController;
use Modules\Finance\App\Http\Controllers\FactureOfficielleController;

Route::prefix('v1/finance')
    ->middleware(['auth:sanctum', 'permission:caisse.access'])
    ->group(function () {

        // --------------------
        // Sessions
        // --------------------
        Route::prefix('sessions')->group(function () {
            Route::post('open', [SessionController::class, 'open'])
                ->middleware('permission:caisse.session.manage');

            Route::post('close', [SessionController::class, 'close'])
                ->middleware('permission:caisse.session.manage');

            Route::get('current', [SessionController::class, 'current'])
                ->middleware('permission:caisse.session.view');
        });

        // --------------------
        // Paiements
        // --------------------
        Route::get('paiements', [PaymentController::class, 'index'])
            ->middleware('permission:caisse.reglement.view');

        Route::post('paiements', [PaymentController::class, 'store'])
            ->middleware('permission:caisse.reglement.create');

        Route::post('paiements/{id}/annuler', [PaymentController::class, 'annuler'])
            ->whereNumber('id')
            ->middleware('permission:caisse.reglement.validate');

        // --------------------
        // Audit
        // --------------------
        Route::get('audit', [AuditController::class, 'index'])
            ->middleware('permission:caisse.audit.view');

        // --------------------
        // Factures (agrégées)
        // --------------------
        Route::get('factures', [FactureController::class, 'index'])
            ->middleware('permission:caisse.facture.view');

        Route::get('factures/{module_source}/{source_id}', [FactureController::class, 'show'])
            ->whereIn('module_source', ['reception', 'pharmacie'])
            ->whereNumber('source_id')
            ->middleware('permission:caisse.facture.view');

        // --------------------
        // Factures officielles
        // --------------------

        // ✅ DOIT être avant {numero_global} sinon conflit
        Route::get('factures-officielles/by-billing-request/{billingRequestId}', [FactureOfficielleController::class, 'byBillingRequest'])
            ->whereNumber('billingRequestId')
            ->middleware('permission:caisse.facture.view');

        Route::get('factures-officielles', [FactureOfficielleController::class, 'index'])
            ->middleware('permission:caisse.facture.view');

        // ✅ Regex optionnelle pour éviter qu’un "by-billing-request" soit interprété ici
        Route::get('factures-officielles/{numero_global}', [FactureOfficielleController::class, 'show'])
            ->where('numero_global', '^(?!by-billing-request$).+')
            ->middleware('permission:caisse.facture.view');
    });