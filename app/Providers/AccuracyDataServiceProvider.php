<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AccuracyDataService;

class AccuracyDataServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
         // Bind the AccuracyDataService to the container
        $this->app->singleton(AccuracyDataService::class, function ($app) {
            return new AccuracyDataService();
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
