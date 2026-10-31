<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Models\Procurement\Procurement;
use App\Observers\ProcurementObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Procurement::observe(ProcurementObserver::class);

        /*
        |--------------------------------------------------------------------------
        | Global Gate Interceptor
        |--------------------------------------------------------------------------
        */
        Gate::before(function (User $user, string $ability) {
            if ($user->isAdmin()) {
                return true;
            }
            // Defer model-driven procurement abilities directly to ProcurementPolicy
            if (in_array($ability, ['route', 'return', 'update', 'cancel', 'complete', 'reject', 'update', 'view'])) {
                return null;
            }
            // Dynamically resolve dot-separated permissions (e.g. 'procurement.view')
            if (str_contains($ability, '.')) {
                return $user->hasPermission($ability);
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Navigation Gate Aliases
        |--------------------------------------------------------------------------
        */
        Gate::define('procurement_access', function (User $user) {
            if ($user->isAdmin()) {
                return true;
            }
            return $user->hasPermission('procurement.view');
        });
    }
}
