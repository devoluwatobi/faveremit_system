<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

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

        Schema::defaultStringLength(191);


        Schema::defaultStringLength(191);

        RateLimiter::for('user', function ($request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        RateLimiter::for('ip', function ($request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('money', function ($request) {
            return Limit::perMinute(1)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
