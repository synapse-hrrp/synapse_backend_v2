<?php

use Illuminate\Support\Facades\Route;
use Modules\Imagerie\App\Http\Controllers\ImagerieController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('imageries', ImagerieController::class)->names('imagerie');
});
