<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display the specified product (same page as /shop/{slug}).
     */
    public function show($slug)
    {
        return app(ShopController::class)->show($slug);
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
            'rating.required' => __('Veuillez sélectionner une note.'),
            'rating.min' => __('La note doit être entre 1 et 5.'),
            'rating.max' => __('La note doit être entre 1 et 5.'),
            'title.required' => __('Le titre est requis.'),
            'title.max' => __('Le titre ne doit pas dépasser 255 caractères.'),
            'comment.required' => __('Le commentaire est requis.'),
        ]);

        // Create the review
        $product->reviews()->create([
            'user_id' => auth()->id(),
            'rating' => $validated['rating'],
            'title' => $validated['title'],
            'comment' => $validated['comment'],
            'is_approved' => false, // Reviews need admin approval
        ]);

        return back()->with('success', __('Votre avis a été soumis et sera publié après modération.'));
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
            'final_price' => $product->final_price,
            'discount_percentage' => $product->discount_percentage,
            'short_description' => $product->short_description,
            'stock' => $product->stock,
            'images' => $product->images->map(function ($image) {
                return [
                    'path' => asset($image->path),
                    'is_primary' => $image->is_primary,
                ];
            }),
            'primary_image' => $product->primaryImage ? asset($product->primaryImage->path) : asset('images/placeholder.jpg'),
            'categories' => $product->categories->map(function ($category) {
                return [
                    'name' => $category->name,
                    'slug' => $category->slug,
                ];
            }),
            'reviews_count' => $product->reviews_count,
            'reviews_avg_rating' => round($product->reviews_avg_rating ?? 0, 1),
            'url' => route('products.show', $product->slug),
            'settings' => [
                'estimated_delivery_days' => getSetting('estimated_delivery_days', '3-5'),
                'free_shipping_threshold' => floatval(getSetting('free_shipping_threshold', '50000')),
            ],
        ]);
    }
}
