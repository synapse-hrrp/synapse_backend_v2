<?php

namespace Modules\Pharmacie;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

// Interfaces
use Modules\Pharmacie\App\Interfaces\StockInterface;
use Modules\Pharmacie\App\Interfaces\PricingInterface;
use Modules\Pharmacie\App\Interfaces\VenteInterface;
use Modules\Pharmacie\App\Interfaces\ReceptionInterface;
use Modules\Pharmacie\App\Interfaces\RapportInterface;

// Services
use Modules\Pharmacie\App\Services\StockService;
use Modules\Pharmacie\App\Services\PricingService;
use Modules\Pharmacie\App\Services\VenteService;
use Modules\Pharmacie\App\Services\ReceptionService;
use Modules\Pharmacie\App\Services\RapportService;
use Modules\Pharmacie\App\Services\DashboardService;
use Modules\Pharmacie\App\Services\EtiquetteService;

// Repositories
use Modules\Pharmacie\App\Repository\StockRepository;
use Modules\Pharmacie\App\Repository\VenteRepository;
use Modules\Pharmacie\App\Repository\ReceptionRepository;
use Modules\Pharmacie\App\Repository\RapportRepository;

// Commands
use Modules\Pharmacie\App\Console\EnvoyerAlertesPeremption;

// Middleware
use Modules\Pharmacie\App\Http\Middleware\CheckPharmaciePermission;

class PharmacieServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'Pharmacie';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'pharmacie';

    /**
     * Register services.
     */
    public function register(): void
    {
        // Enregistrer les Repositories (Singletons pour réutilisation)
        $this->app->singleton(StockRepository::class);
        $this->app->singleton(VenteRepository::class);
        $this->app->singleton(ReceptionRepository::class);
        $this->app->singleton(RapportRepository::class);

        // Lier les Interfaces aux Services (Dependency Injection)
        $this->app->bind(StockInterface::class, StockService::class);
        $this->app->bind(PricingInterface::class, PricingService::class);
        $this->app->bind(VenteInterface::class, VenteService::class);
        $this->app->bind(ReceptionInterface::class, ReceptionService::class);
        $this->app->bind(RapportInterface::class, RapportService::class);

        // Services additionnels
        $this->app->singleton(DashboardService::class);
        $this->app->singleton(EtiquetteService::class);  // ← AJOUTÉ
    }

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        // Charger routes API
        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');

        // Charger migrations du module
        $this->loadMigrationsFrom(dirname(__DIR__) . '/database/migrations');

        // Charger les vues (pour les étiquettes PDF)
        $this->loadViewsFrom(dirname(__DIR__) . '/resources/views', 'pharmacie');  // ← AJOUTÉ

        // Enregistrer le middleware de permissions
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('pharmacie.permission', CheckPharmaciePermission::class);

        // Enregistrer les commandes artisan
        if ($this->app->runningInConsole()) {
            $this->commands([
                EnvoyerAlertesPeremption::class,
            ]);
        }
    }
}