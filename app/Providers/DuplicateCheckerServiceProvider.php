<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\DuplicateCheckerService;

class DuplicateCheckerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(DuplicateCheckerService::class, function ($app) {
            return new DuplicateCheckerService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
