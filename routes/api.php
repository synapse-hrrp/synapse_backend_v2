<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\PersonneController;
use App\Http\Controllers\Api\PatientController;

use Modules\Users\App\Http\Controllers\Api\AuthController;

use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Api\Admin\UserRoleController;
use App\Http\Controllers\Api\DebugController;

Route::prefix('v1')->group(function () {

    // ✅ DEBUG (temporaire)
    Route::middleware('auth:sanctum')->get('debug/can/{ability}', [DebugController::class, 'can']);

    // ✅ AUTH
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });

    // ✅ CONFIG
    Route::middleware('auth:sanctum')->prefix('config')->group(function () {
        Route::get('roles', [ConfigController::class, 'roles']);
        Route::get('modules', [ConfigController::class, 'modules']);
        Route::get('fonctionnalites', [ConfigController::class, 'fonctionnalites']);
        Route::get('fonctionnalites-tree', [ConfigController::class, 'fonctionnalitesTree']);
    });

    // ✅ ADMIN RBAC (permission roles.assign)
    Route::middleware(['auth:sanctum', 'permission:roles.assign'])->prefix('admin')->group(function () {
        Route::post('users/{userId}/roles/sync', [UserRoleController::class, 'syncUserRoles']);
        Route::get('roles/{roleId}/permissions', [AdminRoleController::class, 'permissions']);
        Route::post('roles/{roleId}/permissions/sync', [AdminRoleController::class, 'syncPermissions']);
    });

    // ✅ API PROTÉGÉE
    Route::middleware('auth:sanctum')->group(function () {

        // PERSONNES
        Route::get('personnes', [PersonneController::class, 'index'])
            ->middleware('permission:personnes.view');

        Route::post('personnes', [PersonneController::class, 'store'])
            ->middleware('permission:personnes.create');

        Route::get('personnes/{personne}', [PersonneController::class, 'show'])
            ->middleware('permission:personnes.view');

        Route::match(['put', 'patch'], 'personnes/{personne}', [PersonneController::class, 'update'])
            ->middleware('permission:personnes.update');

        // PATIENTS
        Route::get('patients', [PatientController::class, 'index'])
            ->middleware('permission:patients.view');

        Route::post('patients', [PatientController::class, 'store'])
            ->middleware('permission:patients.create');

        Route::get('patients/{patient}', [PatientController::class, 'show'])
            ->middleware('permission:patients.view');

        Route::match(['put', 'patch'], 'patients/{patient}', [PatientController::class, 'update'])
            ->middleware('permission:patients.update');

        Route::delete('patients/{patient}', [PatientController::class, 'destroy'])
            ->middleware('permission:patients.delete');
    });
});
