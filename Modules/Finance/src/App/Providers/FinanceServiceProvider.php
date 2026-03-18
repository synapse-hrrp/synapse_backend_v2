<?php

namespace Modules\Finance\App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

// ✅ Observers
use Modules\Finance\App\Models\FinancePayment;
use Modules\Finance\App\Models\FactureOfficielle;
use Modules\Finance\App\Observers\FinancePaymentObserver;
use Modules\Finance\App\Observers\FactureOfficielleObserver;

class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        // ✅ migrations
        $this->loadMigrationsFrom(module_path('Finance', 'src/database/migrations'));

        // ✅ routes API
        Route::middleware(['api'])
            ->prefix('api')
            ->group(module_path('Finance', 'src/routes/api.php'));

        // ✅ routes WEB (si besoin)
        Route::middleware(['web'])
            ->group(module_path('Finance', 'src/routes/web.php'));

        // ✅ Déclenchements automatiques
        FinancePayment::observe(FinancePaymentObserver::class);
        FactureOfficielle::observe(FactureOfficielleObserver::class);
    }
}