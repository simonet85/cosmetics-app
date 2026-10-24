<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlist = session()->get('wishlist', []);
        $wishlistItems = [];

        foreach ($wishlist as $productId) {
            // Only products that can still be bought: an inactive or archived one drops out of the list
            $product = Product::with(['images', 'categories'])->active()->find($productId);

            if ($product) {
                $wishlistItems[] = $product;
            }
        }

        return view('wishlist.index', ['wishlistItems' => collect($wishlistItems)]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $wishlist = session()->get('wishlist', []);

        // Check if product already in wishlist
        if (in_array($request->product_id, $wishlist)) {
            return response()->json([
                'success' => false,
                'message' => __('Ce produit est déjà dans votre liste de souhaits.'),
            ], 400);
        }

        $wishlist[] = $request->product_id;
        session()->put('wishlist', $wishlist);

        return response()->json([
            'success' => true,
            'message' => __('Produit ajouté à votre liste de souhaits!'),
            'wishlist_count' => count($wishlist),
        ]);
    }

    public function remove(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $wishlist = session()->get('wishlist', []);

        $key = array_search($request->product_id, $wishlist);
        if ($key !== false) {
            unset($wishlist[$key]);
            $wishlist = array_values($wishlist); // Re-index array
            session()->put('wishlist', $wishlist);

            return response()->json([
                'success' => true,
                'message' => __('Produit retiré de votre liste de souhaits!'),
                'wishlist_count' => count($wishlist),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('Produit non trouvé dans votre liste de souhaits.'),
        ], 404);
    }

    public function clear()
    {
        session()->forget('wishlist');

        return response()->json([
            'success' => true,
            'message' => __('Liste de souhaits vidée avec succès!'),
            'wishlist_count' => 0,
        ]);
    }

    public function count()
    {
        $wishlist = session()->get('wishlist', []);

        return response()->json([
            'count' => count($wishlist),
        ]);
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $wishlist = session()->get('wishlist', []);

        $key = array_search($request->product_id, $wishlist);

        if ($key !== false) {
            // Remove from wishlist
            unset($wishlist[$key]);
            $wishlist = array_values($wishlist);
            session()->put('wishlist', $wishlist);

            return response()->json([
                'success' => true,
                'action' => 'removed',
                'message' => __('Produit retiré de votre liste de souhaits!'),
                'wishlist_count' => count($wishlist),
            ]);
        } else {
            // Add to wishlist
            $wishlist[] = $request->product_id;
            session()->put('wishlist', $wishlist);

            return response()->json([
                'success' => true,
                'action' => 'added',
                'message' => __('Produit ajouté à votre liste de souhaits!'),
                'wishlist_count' => count($wishlist),
            ]);
        }
    }
}
