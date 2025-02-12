<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\BarcodeGeneratorService;

class BarcodeGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(BarcodeGeneratorService::class, function ($app) {
            return new BarcodeGeneratorService();
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
