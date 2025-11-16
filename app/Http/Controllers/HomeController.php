<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // Get hero banners
        $heroBanners = Banner::where('type', 'hero')
            ->active()
            ->orderBy('order')
            ->get();

        // Get promotional banners
        $banners = Banner::where('position', 'promo')
            ->active()
            ->orderBy('order')
            ->get();

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

        return view('home.index', compact(
            'heroBanners',
            'banners',
            'featuredProducts',
            'hotProducts',
            'bestSellers',
            'categories',
            'testimonials'
        ));
    }
}
