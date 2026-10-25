<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Testimonial;
use App\Support\StorefrontTemplates;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // Banners managed in the admin: only live ones (active and inside their dates), one per slot of the page.
        // 'hero' and 'promo' are the positions the demo data used before the admin offered named slots.
        $live = Banner::live()->orderBy('order')->orderBy('id')->get();
        $slotOf = [
            'home_top' => 'top', 'home_slider' => 'top', 'hero' => 'top',
            'home_middle' => 'middle', 'promo' => 'middle',
            'home_bottom' => 'bottom',
        ];
        $banners = [];
        foreach ($live as $banner) {
            $slot = $slotOf[$banner->position] ?? null;
            if ($slot && ! isset($banners[$slot])) {
                $banners[$slot] = $banner;
            }
        }

        // Get featured products with reviews count and average rating
        $featuredProducts = Product::with(['images', 'categories'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->active()
            ->featured()
            ->inStock()
            ->limit(8)
            ->get();

        // Get hot products
        $hotProducts = Product::with(['images', 'categories'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->active()
            ->hot()
            ->inStock()
            ->limit(4)
            ->get();

        // Get best sellers with reviews count and average rating
        $bestSellers = Product::with(['images', 'categories'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->active()
            ->bestSeller()
            ->inStock()
            ->limit(8)
            ->get();

        // Get categories
        $categories = Category::active()
            ->parent()
            ->orderBy('order')
            ->limit(6)
            ->get();

        // The rayons have no photo of their own: each one shows the photo of its first product on sale,
        // and a rayon with nothing on sale is left out (its page would be empty)
        foreach ($categories as $category) {
            $category->setRelation('cover', $category->products()->active()->inStock()->with('images')->orderBy('products.id')->first());
        }
        $categories = $categories->filter(fn ($category) => $category->cover)->values();

        // Get testimonials
        $testimonials = Testimonial::active()
            ->where('show_on_home', true)
            ->orderBy('order')
            ->limit(3)
            ->get();

        if (StorefrontTemplates::for('home', $request) === 'beauty') {
            return view('storefront.home-beauty', [
                'banners' => $banners,
                'featuredProducts' => $featuredProducts,
                'hotProducts' => $hotProducts,
                'bestSellers' => $bestSellers,
                'categories' => $categories,
                'offer' => $this->offer(),
                'wishlistIds' => array_map('intval', session('wishlist', [])),
            ]);
        }

        return view('storefront.home', compact(
            'banners',
            'featuredProducts',
            'hotProducts',
            'bestSellers',
            'categories',
            'testimonials'
        ));
    }

    /** The limited-time offer of the Glowing Beauty home page: shown only while its end date (a setting) is in the future. */
    private function offer(): ?array
    {
        $value = getSetting('home_offer_ends_at');
        if (! filled($value)) {
            return null;
        }

        try {
            $endsAt = Carbon::parse($value, config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }

        return $endsAt->isFuture() ? [
            'ends_at' => $endsAt,
            'title' => getSetting('home_offer_title'),
            'text' => getSetting('home_offer_text'),
        ] : null;
    }
}
