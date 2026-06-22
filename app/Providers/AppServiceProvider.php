<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Dedoc\Scramble\Scramble;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('access-admin', function (User $user) {
            return $user->isAdmin();
        });
        Gate::define('access-leader', function (User $user) {
            return $user->isLeader();
        });
        Gate::define('access-elite', function (User $user) {
            return $user->isElite();
        });

        Scramble::auth(function ($request) {
            if (app()->environment('local')) {
                return true;
            }
            if (config('app.env') === 'production' && env('ALLOW_API_DOCS') === true) {
                return true;
            }
            return false;
        });
    }
}
