<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\BarcodeGeneratorService;
use App\Services\RedisService;

class BarcodeGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Make sure RedisService is registered in the container
        $this->app->singleton(RedisService::class, function ($app) {
            return new RedisService();
        });

        // Now you can register BarcodeGeneratorService and inject RedisService
        $this->app->singleton(BarcodeGeneratorService::class, function ($app) {
            return new BarcodeGeneratorService($app->make(RedisService::class));
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
