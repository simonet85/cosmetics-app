<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['images', 'categories'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->active()
            ->inStock();

        // Filter by category
        if ($request->has('category') && $request->category != '') {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Filter by rating
        if ($request->has('rating') && $request->rating > 0) {
            $query->whereHas('reviews', function ($q) use ($request) {
                $q->havingRaw('AVG(rating) >= ?', [$request->rating]);
            });
        }

        // Search by keyword
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('short_description', 'like', '%' . $request->search . '%')
                    ->orWhere('full_description', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by tag
        if ($request->has('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->tag);
            });
        }

        // Sort options
        $sortBy = $request->get('sort', 'latest');
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'popularity':
                $query->withCount('orderItems')->orderBy('order_items_count', 'desc');
                break;
            case 'rating':
                $query->orderBy('reviews_avg_rating', 'desc');
                break;
            default: // latest
                $query->orderBy('created_at', 'desc');
                break;
        }

        $perPage = $request->get('per_page', 12);
        $products = $query->paginate($perPage)->withQueryString();

        // Get all categories for filter
        $categories = Category::active()->parent()->orderBy('name')->get();

        // Get price range
        $priceRange = Product::active()->selectRaw('MIN(price) as min, MAX(price) as max')->first();

        return view('shop.index', compact('products', 'categories', 'priceRange'));
    }

    public function show($slug)
    {
        $product = Product::with(['images', 'categories', 'tags', 'variants', 'reviews.user'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();

        // Get related products from same categories
        $categoryIds = $product->categories->pluck('id');
        $relatedProducts = Product::with(['images', 'categories'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('category_id', $categoryIds);
            })
            ->where('id', '!=', $product->id)
            ->active()
            ->inStock()
            ->limit(4)
            ->get();

        return view('shop.show', compact('product', 'relatedProducts'));
    }

    /**
     * Display products by category.
     */
    public function category($slug)
    {
        $category = Category::where('slug', $slug)->active()->firstOrFail();

        $products = Product::with(['images', 'categories'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->whereHas('categories', function ($q) use ($category) {
                $q->where('category_id', $category->id);
            })
            ->active()
            ->inStock()
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Get all categories for filter
        $categories = Category::active()->parent()->orderBy('name')->get();

        // Get price range
        $priceRange = Product::active()->selectRaw('MIN(price) as min, MAX(price) as max')->first();

        return view('shop.index', compact('products', 'categories', 'priceRange', 'category'));
    }
}
