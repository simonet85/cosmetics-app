<?php

namespace App\Providers;

use App\View\Composers\StorefrontComposer;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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
        View::composer(['components.storefront.header', 'components.storefront.footer'], StorefrontComposer::class);

        // Forcer HTTPS en développement avec ngrok
        if ($this->app->environment('local') && config('app.force_https', false)) {
            URL::forceScheme('https');
        }

        // Ou simplement détecter le proxy
        if (request()->header('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }
    }
}
