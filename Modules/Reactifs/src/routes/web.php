<?php

use Illuminate\Support\Facades\Route;
use Modules\Reactifs\App\Http\Controllers\ReactifsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('reactifs', ReactifsController::class)->names('reactifs');
});
