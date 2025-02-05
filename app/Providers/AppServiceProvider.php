<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Client Secret hashing
        Passport::hashClientSecrets();

        // Token life time 
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));
        
        RateLimiter::for('duplicates', function ($request) {
            $ip = $request->ip();
            if ($this->isExcludedSubnet($ip)) {
                return Limit::none();
            }
            return Limit::perHour(4)->by($ip);
        });
    }

    /**
     * Determine if the given IP is within the excluded subnets.
     */
    protected function isExcludedSubnet($ip): bool
    {
        $excludedSubnets = [
            '10.12.0.0/20',
            '10.12.28.0/24',
        ];

        foreach ($excludedSubnets as $subnet) {
            if ($this->ipInSubnet($ip, $subnet)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP is within a given subnet.
     */
    protected function ipInSubnet($ip, $subnet): bool
    {
        list($subnet, $mask) = explode('/', $subnet);
        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet);
    }
}
