<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tag;
use App\Support\StorefrontTemplates;
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
    /** Page sizes offered by the shop list. */
    private const PER_PAGE = [9, 12, 18, 24];

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
            // An unknown value (an old bookmarked link) is not an error: the list simply uses the default order
            'sort' => ['nullable', 'string', 'max:30'],
            'sold_out' => ['nullable', 'boolean'],
            'on_sale' => ['nullable', 'boolean'],
            'capacity' => ['nullable', 'array', 'max:10'],
            'capacity.*' => ['string', 'max:50'],
            // Unknown values are not an error: the list falls back to its default
            'per_page' => ['nullable', 'string', 'max:5'],
            'cols' => ['nullable', 'string', 'max:2'],
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

        if ($filters['on_sale'] ?? false) {
            $query->where('discount_price', '>', 0);
        }

        if (! empty($filters['capacity'])) {
            $query->whereHas('variants', fn ($q) => $q->where('is_active', true)->whereIn('size', $filters['capacity']));
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

        $sort = array_key_exists($filters['sort'] ?? '', self::SORTS) ? $filters['sort'] : 'latest';
        match ($sort) {
            'price_asc' => $query->orderByRaw(self::EFFECTIVE_PRICE.' asc'),
            'price_desc' => $query->orderByRaw(self::EFFECTIVE_PRICE.' desc'),
            'name_asc' => $query->orderBy('name'),
            'popularity' => $query->withCount('orderItems')->orderByDesc('order_items_count'),
            default => $query->latest(),
        };
        $query->orderBy('id');

        $perPage = in_array((int) ($filters['per_page'] ?? 0), self::PER_PAGE, true) ? (int) $filters['per_page'] : 12;
        $columns = in_array((int) ($filters['cols'] ?? 0), [2, 3, 4], true) ? (int) $filters['cols'] : 3;

        $products = $query->paginate($perPage)->withQueryString();

        $categories = Category::active()->parent()->orderBy('order')->orderBy('name')->get();
        $tags = Tag::whereHas('products', fn ($q) => $q->active()->inStock())->orderBy('name')->get();

        // Placeholder range in whole francs, from the catalogue itself
        $range = Product::active()->inStock()
            ->selectRaw('MIN('.self::EFFECTIVE_PRICE.') as min, MAX('.self::EFFECTIVE_PRICE.') as max')->first();

        $activeCategory = $category ?? ($categorySlug ? $categories->firstWhere('slug', $categorySlug) : null);

        $template = StorefrontTemplates::for('shop', $request);

        return view($template === 'beauty' ? 'storefront.shop-beauty' : 'storefront.shop', [
            'perPage' => $perPage,
            'perPageOptions' => self::PER_PAGE,
            'columns' => $columns,
            ...($template === 'beauty' ? $this->beautyExtras() : []),
            'products' => $products,
            'categories' => $categories,
            'tags' => $tags,
            'sorts' => array_map(fn ($label) => __($label), self::SORTS),
            'sort' => $sort,
            'filters' => $filters,
            'activeCategory' => $activeCategory,
            'categorySlug' => $categorySlug,
            'priceRange' => ['min' => (int) floor((float) ($range->min ?? 0)), 'max' => (int) ceil((float) ($range->max ?? 0))],
        ]);
    }

    /** Extra data of the Glowing Beauty list: category counts, capacities, best rated products, favourites. */
    private function beautyExtras(): array
    {
        $capacities = ProductVariant::query()
            ->where('is_active', true)->whereNotNull('size')->where('size', '!=', '')
            ->whereHas('product', fn ($q) => $q->active()->inStock())
            ->selectRaw('size, COUNT(DISTINCT product_id) as products_count')
            ->groupBy('size')->orderBy('size')->get();

        $topRated = Product::with('images')->active()->inStock()
            ->withAvg(['reviews as rating_average' => fn ($q) => $q->approved()], 'rating')
            ->get()->filter(fn ($p) => $p->rating_average !== null)
            ->sortByDesc('rating_average')->take(3)->values();

        $stripCategories = Category::active()->parent()->orderBy('order')->orderBy('name')
            ->withCount(['products as sellable_count' => fn ($q) => $q->active()->inStock()])->get()
            ->filter(fn ($category) => $category->sellable_count > 0)->values();   // a rayon with nothing on sale leads nowhere

        return [
            'capacities' => $capacities,
            'topRated' => $topRated,
            'stripCategories' => $stripCategories,
            'wishlistIds' => array_map('intval', session('wishlist', [])),
        ];
    }

    public function show($slug)
    {
        $product = Product::with(['images', 'categories', 'tags', 'variants' => fn ($q) => $q->where('is_active', true)->orderBy('id')])
            ->where('slug', $slug)
            // Admins can preview unpublished (inactive / archived) products from the admin list
            ->when(! (auth()->check() && auth()->user()->isAdmin()), fn ($query) => $query->active())
            ->firstOrFail();

        $isPreview = $product->status !== 'active';

        // Only moderated reviews are shown and counted
        $reviews = $product->reviews()->approved()->with('user')->latest()->get();

        // Get related products from same categories
        $categoryIds = $product->categories->pluck('id');
        $relatedProducts = Product::with(['images', 'categories'])
            ->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('category_id', $categoryIds);
            })
            ->where('id', '!=', $product->id)
            ->active()
            ->inStock()
            ->limit(4)
            ->get();

        return view('storefront.product', [
            'product' => $product,
            'isPreview' => $isPreview,
            'reviews' => $reviews,
            'reviewCount' => $reviews->count(),
            'reviewAverage' => $reviews->count() ? round($reviews->avg('rating'), 1) : null,
            'relatedProducts' => $relatedProducts,
            'inWishlist' => in_array($product->id, session('wishlist', [])),
        ]);
    }
}
