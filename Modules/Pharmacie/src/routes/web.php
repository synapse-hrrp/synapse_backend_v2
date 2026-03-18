<?php

use Illuminate\Support\Facades\Route;
use Modules\Pharmacie\App\Http\Controllers\PharmacieController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('pharmacies', PharmacieController::class)->names('pharmacie');
});
