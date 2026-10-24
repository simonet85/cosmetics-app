<?php

namespace App\View\Composers;

use App\Models\Category;
use App\Support\PaymentMethods;
use Illuminate\View\View;

/**
 * Data the storefront header and footer need on every page, all read from the database and the settings.
 */
class StorefrontComposer
{
    public function compose(View $view): void
    {
        $cart = session('cart', []);

        $wishlistCount = auth()->check()
            ? auth()->user()->wishlist()->count()
            : count(session('wishlist', []));

        $view->with([
            'navCategories' => Category::active()->parent()->orderBy('order')->limit(5)->get(['id', 'name', 'slug']),
            'freeShippingThreshold' => (float) getSetting('free_shipping_threshold', '50000'),
            'paymentLabels' => PaymentMethods::acceptedLabels(),
            'cartCount' => (int) array_sum(array_column($cart, 'quantity')),
            'wishlistCount' => $wishlistCount,
        ]);
    }
}
