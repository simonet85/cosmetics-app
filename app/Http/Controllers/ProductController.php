<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display the specified product.
     */
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
     * Store a review for the product.
     */
    public function storeReview(Request $request, $slug)
    {
        $product = Product::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'required|string|max:255',
            'comment' => 'required|string',
        ], [
            'rating.required' => 'Veuillez sélectionner une note.',
            'rating.min' => 'La note doit être entre 1 et 5.',
            'rating.max' => 'La note doit être entre 1 et 5.',
            'title.required' => 'Le titre est requis.',
            'title.max' => 'Le titre ne doit pas dépasser 255 caractères.',
            'comment.required' => 'Le commentaire est requis.',
        ]);

        // Create the review
        $product->reviews()->create([
            'user_id' => auth()->id(),
            'rating' => $validated['rating'],
            'title' => $validated['title'],
            'comment' => $validated['comment'],
            'is_approved' => false, // Reviews need admin approval
        ]);

        return back()->with('success', 'Votre avis a été soumis et sera publié après modération.');
    }

    /**
     * Get product data for quick view modal.
     */
    public function quickView($slug)
    {
        $product = Product::with(['images', 'categories'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'price' => $product->price,
            'compare_price' => $product->compare_price,
            'discount_percentage' => $product->discount_percentage,
            'short_description' => $product->short_description,
            'stock' => $product->stock,
            'images' => $product->images->map(function($image) {
                return [
                    'path' => asset($image->path),
                    'is_primary' => $image->is_primary
                ];
            }),
            'primary_image' => $product->primaryImage ? asset($product->primaryImage->path) : asset('images/placeholder.jpg'),
            'categories' => $product->categories->map(function($category) {
                return [
                    'name' => $category->name,
                    'slug' => $category->slug
                ];
            }),
            'reviews_count' => $product->reviews_count,
            'reviews_avg_rating' => round($product->reviews_avg_rating ?? 0, 1),
            'url' => route('products.show', $product->slug)
        ]);
    }
}
