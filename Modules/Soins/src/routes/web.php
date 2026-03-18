<?php

use Illuminate\Support\Facades\Route;
use Modules\Soins\App\Http\Controllers\SoinsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('soins', SoinsController::class)->names('soins');
});
