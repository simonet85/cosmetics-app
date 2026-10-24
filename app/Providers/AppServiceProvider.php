<?php

namespace App\Providers;

use App\Services\CartService;
use App\View\Composers\StorefrontComposer;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
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
        $this->app->singleton(\App\Support\Translations::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['components.storefront.header', 'components.storefront.footer'], StorefrontComposer::class);

        // A customer who signs in gets back the cart saved with their account, merged with this browser's cart
        Event::listen(Login::class, fn (Login $event) => app(CartService::class)->mergeForUser($event->user));

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
