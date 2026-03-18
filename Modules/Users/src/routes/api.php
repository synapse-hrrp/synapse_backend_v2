<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\App\Http\Controllers\Api\AuthController;
use Modules\Users\App\Http\Controllers\Api\AgentController;
use Modules\Users\App\Http\Controllers\Api\UserController;

Route::prefix('v1')->group(function () {

    // ✅ AUTH
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->name('auth.login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthController::class, 'me'])->name('auth.me');
            Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
        });
    });

    // ✅ AGENTS (protégé)
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('agents', AgentController::class);
    });
     

    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('agents', AgentController::class);

        // ✅ USERS
        Route::apiResource('users', UserController::class);

        // ✅ roles endpoints
        Route::post('users/{id}/roles', [UserController::class, 'syncRoles'])->whereNumber('id');
        Route::post('users/{id}/roles/attach', [UserController::class, 'attachRole'])->whereNumber('id');
    });

});
