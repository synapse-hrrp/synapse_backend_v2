<?php

namespace Modules\Finance\App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        $this->routes(function () {
            Route::middleware('api')
                ->group(module_path('Finance', 'src/routes/api.php'));

            Route::middleware('web')
                ->group(module_path('Finance', 'src/routes/web.php'));
        });
    }
}
