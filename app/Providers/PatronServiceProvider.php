<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PatronDataTransformer;

class PatronServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     * 
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(PatronDataTransformer::class, function ($app) {
            return new PatronDataTransformer();
        });
    }

    /**
     * Bootstrap services.
     * 
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
