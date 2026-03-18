<?php

use Illuminate\Support\Facades\Route;
use Modules\Laboratoire\App\Http\Controllers\LaboratoireController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('laboratoires', LaboratoireController::class)->names('laboratoire');
});
