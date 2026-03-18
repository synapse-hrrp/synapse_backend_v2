<?php

namespace Modules\Finance\App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

use Modules\Finance\App\Models\FinancePayment;
use Modules\Finance\App\Policies\FinancePaymentPolicy;

class FinanceAuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        FinancePayment::class => FinancePaymentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Gate custom (optionnel) si tu veux l'utiliser directement
        Gate::define('finance.payment.cancel', [FinancePaymentPolicy::class, 'cancel']);
    }
}