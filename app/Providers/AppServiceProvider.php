<?php

namespace App\Providers;

use App\Notifications\Channels\LogChannel;
use App\Services\CartService;
use App\Services\MoneyFusionService;
use App\View\Composers\StorefrontComposer;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Simonet85\LaravelMoneyFusion\MoneyFusionService as PackageMoneyFusionService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Support\Translations::class);

        // The package's amount check ignores its own fees, so paid sessions were never credited
        $this->app->bind(PackageMoneyFusionService::class, MoneyFusionService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['components.storefront.header', 'components.storefront.footer'], StorefrontComposer::class);

        // A customer who signs in gets back the cart saved with their account, merged with this browser's cart
        Event::listen(Login::class, fn (Login $event) => app(CartService::class)->mergeForUser($event->user));

        // SMS fallback in local/testing: the one-time code is written to the log instead of being sent
        Notification::extend('log', fn () => new LogChannel);

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
