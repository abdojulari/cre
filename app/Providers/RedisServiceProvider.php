<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\RedisService;

class RedisServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind the RedisService to the container
        $this->app->singleton(RedisService::class, function ($app) {
            return new RedisService();
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
