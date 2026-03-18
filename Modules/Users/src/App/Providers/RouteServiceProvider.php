<?php

namespace Modules\Users\App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Users';

    public function boot(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    protected function mapApiRoutes(): void
    {
        $apiPath = module_path($this->name, 'src/routes/api.php');

        if (file_exists($apiPath)) {
            Route::middleware('api')
                ->prefix('api')
                ->group($apiPath);
        }
    }

    protected function mapWebRoutes(): void
    {
        
    }
}
