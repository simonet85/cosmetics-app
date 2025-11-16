<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CustomMoneyFusionService;
use Simonet85\LaravelMoneyFusion\MoneyFusionService;

class CustomMoneyFusionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Remplacer le service MoneyFusion par notre version personnalisée
        $this->app->singleton(MoneyFusionService::class, function ($app) {
            return new CustomMoneyFusionService();
        });

        // Alias pour faciliter l'utilisation
        $this->app->alias(MoneyFusionService::class, 'moneyfusion');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
