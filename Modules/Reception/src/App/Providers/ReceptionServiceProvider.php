<?php

namespace Modules\Reception\App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;

use Modules\Reception\App\Http\Middleware\EnsureUserHasAgent;
use Modules\Reception\App\Policies\TariffPolicy;

// ✅ Ajout observer DailyRegisterEntry
use Modules\Reception\App\Models\DailyRegisterEntry;
use Modules\Reception\App\Observers\DailyRegisterEntryObserver;

// ✅ Ajout observer BillingRequestItem (création ExamenRequest pending_payment)
use Modules\Reception\App\Models\BillingRequestItem;
use Modules\Reception\App\Observers\BillingRequestItemObserver;

class ReceptionServiceProvider extends ServiceProvider
{
    protected string $name = 'Reception';

    public function register(): void
    {
        $this->app->singleton(
            \Modules\Reception\App\Services\TariffResolverService::class
        );

        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        // ✅ migrations module
        $this->loadMigrationsFrom(module_path($this->name, 'src/database/migrations'));

        // ✅ alias middleware (agent.required)
        /** @var Router $router */
        $router = $this->app['router'];
        $router->aliasMiddleware('agent.required', EnsureUserHasAgent::class);

        // ✅ Gates
        Gate::define('tariff.view', [TariffPolicy::class, 'view']);
        Gate::define('tariff.manage', [TariffPolicy::class, 'manage']);

        // ✅ Observer: auto-fill id_agent_createur (agent connecté)
        DailyRegisterEntry::observe(DailyRegisterEntryObserver::class);

        // ✅ Observer: à chaque ligne de facturation LABO -> créer ExamenRequest(pending_payment)
        BillingRequestItem::observe(BillingRequestItemObserver::class);
    }
}