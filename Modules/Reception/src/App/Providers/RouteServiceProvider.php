<?php

namespace Modules\Reception\App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // ✅ Alias middleware
        $this->app['router']->aliasMiddleware(
            'agent.required',
            \Modules\Reception\App\Http\Middleware\EnsureUserHasAgent::class
        );

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api') // ✅ AJOUT IMPORTANT => /api/v1/reception/...
                ->group(module_path('Reception', 'src/Routes/api.php'));
        });
    }
}
