<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MoneyFusionCompatibilityServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Create an alias for the missing Controller class
        if (!class_exists('Simonet85\LaravelMoneyFusion\Http\Controllers\Controller')) {
            class_alias(
                \Illuminate\Routing\Controller::class,
                'Simonet85\LaravelMoneyFusion\Http\Controllers\Controller'
            );
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
