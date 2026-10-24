<?php

namespace App\View\Composers;

use App\Models\Category;
use App\Support\PaymentMethods;
use App\Support\Shipping;
use Illuminate\View\View;

/**
 * Data the storefront header and footer need on every page, all read from the database and the settings.
 */
class StorefrontComposer
{
    public function compose(View $view): void
    {
        $cart = session('cart', []);

        // The wishlist lives in the session for everyone (that is what the pages and the toggle read and write)
        $wishlistCount = count(session('wishlist', []));

        $view->with([
            'navCategories' => Category::active()->parent()->orderBy('order')->limit(5)->get(['id', 'name', 'slug']),
            'shippingPromise' => Shipping::promise(),
            'paymentLabels' => PaymentMethods::acceptedLabels(),
            'cartCount' => (int) array_sum(array_column($cart, 'quantity')),
            'wishlistCount' => $wishlistCount,
        ]);
    }
}
