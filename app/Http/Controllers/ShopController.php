<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    private const SORTS = [
        'latest' => 'Nouveautés',
        'price_asc' => 'Prix croissant',
        'price_desc' => 'Prix décroissant',
        'name_asc' => 'Nom A à Z',
        'popularity' => 'Popularité',
    ];

    /** SQL for the price the customer actually pays (discount price when there is one). */
    private const EFFECTIVE_PRICE = 'COALESCE(NULLIF(discount_price, 0), price)';

    public function index(Request $request)
    {
        return $this->listing($request, null);
    }

    /**
     * Display products by category.
     */
    public function category(Request $request, Category $category)
    {
        abort_unless($category->is_active, 404);

        return $this->listing($request, $category);
    }

    private function listing(Request $request, ?Category $category)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:150'],
            'tag' => ['nullable', 'string', 'max:150'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'sort' => ['nullable', 'in:'.implode(',', array_keys(self::SORTS))],
            'sold_out' => ['nullable', 'boolean'],
        ]);

        $categorySlug = $category?->slug ?? ($filters['category'] ?? null);

        $query = Product::with(['images', 'categories'])->active();

        if (! ($filters['sold_out'] ?? false)) {
            $query->inStock();
        }

        if ($categorySlug) {
            $query->whereHas('categories', fn ($q) => $q->where('slug', $categorySlug));
        }

        if (! empty($filters['tag'])) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $filters['tag']));
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== null) {
            $query->whereRaw(self::EFFECTIVE_PRICE.' >= ?', [$filters['min_price']]);
        }
        if (isset($filters['max_price']) && $filters['max_price'] !== null) {
            $query->whereRaw(self::EFFECTIVE_PRICE.' <= ?', [$filters['max_price']]);
        }

        if (! empty($filters['search'])) {
            $term = '%'.addcslashes($filters['search'], '%_\\').'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('short_description', 'like', $term)
                    ->orWhere('full_description', 'like', $term);
            });
        }

        $sort = $filters['sort'] ?? 'latest';
        match ($sort) {
            'price_asc' => $query->orderByRaw(self::EFFECTIVE_PRICE.' asc'),
            'price_desc' => $query->orderByRaw(self::EFFECTIVE_PRICE.' desc'),
            'name_asc' => $query->orderBy('name'),
            'popularity' => $query->withCount('orderItems')->orderByDesc('order_items_count'),
            default => $query->latest(),
        };
        $query->orderBy('id');

        $products = $query->paginate(12)->withQueryString();

        $categories = Category::active()->parent()->orderBy('order')->orderBy('name')->get();
        $tags = Tag::whereHas('products', fn ($q) => $q->active()->inStock())->orderBy('name')->get();

        // Placeholder range in whole francs, from the catalogue itself
        $range = Product::active()->inStock()
            ->selectRaw('MIN('.self::EFFECTIVE_PRICE.') as min, MAX('.self::EFFECTIVE_PRICE.') as max')->first();

        $activeCategory = $category ?? ($categorySlug ? $categories->firstWhere('slug', $categorySlug) : null);

        return view('storefront.shop', [
            'products' => $products,
            'categories' => $categories,
            'tags' => $tags,
            'sorts' => self::SORTS,
            'sort' => $sort,
            'filters' => $filters,
            'activeCategory' => $activeCategory,
            'categorySlug' => $categorySlug,
            'priceRange' => ['min' => (int) floor((float) ($range->min ?? 0)), 'max' => (int) ceil((float) ($range->max ?? 0))],
        ]);
    }

    public function show($slug)
    {
        $product = Product::with(['images', 'categories', 'tags', 'variants', 'reviews.user'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->where('slug', $slug)
            // Admins can preview unpublished (inactive / archived) products from the admin list
            ->when(! (auth()->check() && auth()->user()->isAdmin()), fn ($query) => $query->active())
            ->firstOrFail();

        $isPreview = $product->status !== 'active';

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

        return view('shop.show', compact('product', 'relatedProducts', 'isPreview'));
    }
}
