<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use Modules\Reception\App\Http\Controllers\Api\RegisterController;
use Modules\Reception\App\Http\Controllers\Api\RegisterItemsController;
use Modules\Reception\App\Http\Controllers\Api\RegisterBillingController;

use Modules\Reception\App\Http\Controllers\Api\BillableServicesController;
use Modules\Reception\App\Http\Controllers\Api\BillingRequestsController;

use Modules\Reception\App\Http\Controllers\Api\AppointmentsController;
use Modules\Reception\App\Http\Controllers\Api\DoctorsController;
use Modules\Reception\App\Http\Controllers\Api\TariffController;
use Modules\Reception\App\Http\Controllers\Api\TariffPlansController;
use Modules\Reception\App\Http\Controllers\Api\TariffItemsController;

Route::prefix('v1/reception')
    ->middleware('auth:sanctum')
    ->group(function () {

        // ✅ Debug: utilisateur connecté
        Route::get('me', function (Request $request) {
            return response()->json([
                'ok' => true,
                'user' => $request->user(),
                'agent_id' => $request->user()?->agent_id,
            ]);
        });

        // ── Services (BillableService) ───────────────────────────
        Route::get('services', [BillableServicesController::class, 'index']);
        Route::get('services/{service}', [BillableServicesController::class, 'show']);
        Route::post('services', [BillableServicesController::class, 'store']);
        Route::patch('services/{service}', [BillableServicesController::class, 'update']);
        Route::post('services/upsert', [BillableServicesController::class, 'upsert']);
        Route::delete('services/{service}', [BillableServicesController::class, 'destroy']);

        // ── Registre ─────────────────────────────────────────────
        Route::get('register', [RegisterController::class, 'index']);
        Route::post('register', [RegisterController::class, 'store']);
        Route::get('register/{id}', [RegisterController::class, 'show'])->whereNumber('id');
        Route::patch('register/{id}', [RegisterController::class, 'update'])->whereNumber('id');
        Route::delete('register/{id}', [RegisterController::class, 'destroy'])->whereNumber('id');

        // ❌ SUPPRIMÉ: updateStatus (ne pas exposer une route qui peut forcer paid/awaiting)
        // Route::patch('register/{id}/status', [RegisterController::class, 'updateStatus'])->whereNumber('id');

        Route::get('patients-today', [RegisterController::class, 'patientsToday']);

        // ✅ CLOSE / CANCEL (corrigé: pas de double "reception/")
        Route::post('register/{id}/close', [RegisterController::class, 'close'])->whereNumber('id');
        Route::post('register/{id}/cancel', [RegisterController::class, 'cancel'])->whereNumber('id');

        // Plan tarifaire (si tu gardes)
        Route::patch('register/{id}/tariff-plan', [RegisterController::class, 'setTariffPlan'])
            ->whereNumber('id');

        // ── Items registre ───────────────────────────────────────
        Route::post('register/{entryId}/items', [RegisterItemsController::class, 'store'])
            ->whereNumber('entryId');
        Route::delete('register/{entryId}/items/{itemId}', [RegisterItemsController::class, 'destroy'])
            ->whereNumber('entryId')->whereNumber('itemId');

        // ── Billing bridge ───────────────────────────────────────
        Route::post('register/{id}/billing-request', [RegisterBillingController::class, 'generate'])
            ->whereNumber('id');

        // BillingRequest (show / create-from-register si tu l'utilises)
        Route::get('billing-requests/{id}', [BillingRequestsController::class, 'show'])
            ->whereNumber('id');

        // (OPTIONNEL) Si ton front crée encore des BR depuis l'entrée via ce controller:
        Route::post('register/{id}/billing-requests', [BillingRequestsController::class, 'createFromRegister'])
            ->whereNumber('id');

        // ❌ SUPPRIMÉ: paiement côté réception (désormais uniquement Finance)
        // Route::post('billing-requests/{id}/pay', [BillingPaymentsController::class, 'pay'])->whereNumber('id');

        // ── Rendez-vous ─────────────────────────────────────────
        Route::get('appointments', [AppointmentsController::class, 'index']);
        Route::post('appointments', [AppointmentsController::class, 'store']);
        Route::get('appointments/{id}', [AppointmentsController::class, 'show'])->whereNumber('id');
        Route::patch('appointments/{id}', [AppointmentsController::class, 'update'])->whereNumber('id');
        Route::patch('appointments/{id}/status', [AppointmentsController::class, 'updateStatus'])->whereNumber('id');
        Route::delete('appointments/{id}', [AppointmentsController::class, 'destroy'])->whereNumber('id');
        Route::get('appointments/availability', [AppointmentsController::class, 'availability']);
        Route::post('register/{id}/appointments', [AppointmentsController::class, 'createFromRegister'])->whereNumber('id');
        Route::patch('appointments/{id}/reschedule', [AppointmentsController::class, 'reschedule'])->whereNumber('id');
        Route::get('appointments/day', [AppointmentsController::class, 'dayView']);

        // ── Médecins ────────────────────────────────────────────
        Route::get('doctors', [DoctorsController::class, 'index']);

        // ── Tarification (lecture) ───────────────────────────────
        Route::prefix('tariffs')->group(function () {
            Route::get('plans', [TariffController::class, 'plans']);
            Route::get('services', [TariffController::class, 'services']);
        });

        // ── Tarifs (ADMIN / CRUD) ───────────────────────────────
        Route::prefix('tariffs')->group(function () {

            // Plans (CRUD)
            Route::get('plans/{id}', [TariffPlansController::class, 'show'])->whereNumber('id');
            Route::post('plans', [TariffPlansController::class, 'store']);
            Route::patch('plans/{id}', [TariffPlansController::class, 'update'])->whereNumber('id');
            Route::delete('plans/{id}', [TariffPlansController::class, 'destroy'])->whereNumber('id');

            // Items (CRUD)
            Route::get('plans/{planId}/items', [TariffItemsController::class, 'index'])->whereNumber('planId');
            Route::post('plans/{planId}/items', [TariffItemsController::class, 'store'])->whereNumber('planId');
            Route::patch('items/{id}', [TariffItemsController::class, 'update'])->whereNumber('id');
            Route::delete('items/{id}', [TariffItemsController::class, 'destroy'])->whereNumber('id');
        });
    });