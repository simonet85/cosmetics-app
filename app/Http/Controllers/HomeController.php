<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Testimonial;

class HomeController extends Controller
{
    public function index()
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

        // Get testimonials
        $testimonials = Testimonial::active()
            ->where('show_on_home', true)
            ->orderBy('order')
            ->get();

        return view('storefront.home', compact(
            'banners',
            'featuredProducts',
            'hotProducts',
            'bestSellers',
            'categories',
            'testimonials'
        ));
    }
}
