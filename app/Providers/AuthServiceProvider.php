<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::before(function ($user, string $ability) {

            // admin = tout
            if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
                return true;
            }

            // permission via fonctionnalites.tech_label
            if (method_exists($user, 'hasPermission') && $user->hasPermission($ability)) {
                return true;
            }

            return null;
        });
    }
}
