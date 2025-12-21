<?php

namespace App\Providers;

use App\Auth\CustomUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
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
        Auth::provider('custom', function ($app, array $config) {
            return new CustomUserProvider($app['hash'], $config['model']);
        });

        if (!$this->app->runningInConsole()) {
            // Ensure generated URLs (asset, route) always use HTTPS in production to avoid mixed content
            if (config('app.env') === 'production') {
                URL::forceScheme('https');
            }
            URL::forceRootUrl(request()->getSchemeAndHttpHost());
        }

        \Carbon\Carbon::setLocale(config('app.locale'));

        
        Paginator::useBootstrapFive();
    }
}
