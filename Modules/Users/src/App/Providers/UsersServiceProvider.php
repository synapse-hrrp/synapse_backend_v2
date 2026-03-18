<?php

namespace Modules\Users\App\Providers;

use Illuminate\Support\ServiceProvider;

class UsersServiceProvider extends ServiceProvider
{
    protected string $name = 'Users';

    public function register(): void
    {
        // Charger les routes du module
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        // Charger les migrations du module
        $this->loadMigrationsFrom(module_path($this->name, 'src/database/migrations'));

        // API-only => pas de views, pas de translations dans le module
        // Lang global au niveau racine: /lang
    }
}
