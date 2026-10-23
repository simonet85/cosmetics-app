<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index()
    {
        $cartItems = array_values($this->cart->lines());

        ['subtotal' => $subtotal, 'tax' => $tax, 'shipping' => $shipping, 'total' => $total] = $this->cart->totals();

        return view('cart.index', compact('cartItems', 'subtotal', 'tax', 'shipping', 'total'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $product = Product::active()->find($request->product_id);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Ce produit n\'est plus disponible.',
            ], 422);
        }

        // Check stock
        if ($product->stock < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuffisant pour ce produit.',
            ], 400);
        }

        if ($request->variant_id) {
            $variant = ProductVariant::where('product_id', $product->id)
                ->where('is_active', true)
                ->find($request->variant_id);

            if (! $variant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette variante n\'est pas disponible pour ce produit.',
                ], 422);
            }

            if ($variant->stock < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuffisant pour cette variante.',
                ], 400);
            }
        }

        $cart = session()->get('cart', []);

        // Create unique cart item ID
        $cartItemId = $request->product_id.($request->variant_id ? '_'.$request->variant_id : '');

        // The session only remembers the choice; prices are computed from the database (CartService)
        if (isset($cart[$cartItemId])) {
            $newQuantity = $cart[$cartItemId]['quantity'] + $request->quantity;

            if ($product->stock < $newQuantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuffisant pour cette quantité.',
                ], 400);
            }

            $cart[$cartItemId]['quantity'] = $newQuantity;
        } else {
            $cart[$cartItemId] = [
                'product_id' => (int) $request->product_id,
                'variant_id' => $request->variant_id ? (int) $request->variant_id : null,
                'quantity' => (int) $request->quantity,
            ];
        }

        session()->put('cart', $cart);

        return response()->json([
            'success' => true,
            'message' => 'Produit ajouté au panier avec succès!',
            'cart_count' => $this->cart->count(),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'cart_item_id' => 'required|string',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = session()->get('cart', []);

        if (! isset($cart[$request->cart_item_id])) {
            return response()->json([
                'success' => false,
                'message' => 'Article non trouvé dans le panier.',
            ], 404);
        }

        $product = Product::find($cart[$request->cart_item_id]['product_id']);

        if (! $product || $product->stock < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuffisant pour cette quantité.',
            ], 400);
        }

        $cart[$request->cart_item_id]['quantity'] = (int) $request->quantity;
        session()->put('cart', $cart);

        $lines = $this->cart->lines();
        $totals = $this->cart->totals($lines);

        return response()->json([
            'success' => true,
            'message' => 'Panier mis à jour avec succès!',
            'cart_count' => $this->cart->count(),
            'item_subtotal' => number_format($lines[$request->cart_item_id]['subtotal'] ?? 0, 2),
            'subtotal' => number_format($totals['subtotal'], 2),
            'tax' => number_format($totals['tax'], 2),
            'shipping' => number_format($totals['shipping'], 2),
            'total' => number_format($totals['total'], 2),
        ]);
    }

    public function remove(Request $request)
    {
        $request->validate([
            'cart_item_id' => 'required|string',
        ]);

        $cart = session()->get('cart', []);

        if (! isset($cart[$request->cart_item_id])) {
            return response()->json([
                'success' => false,
                'message' => 'Article non trouvé dans le panier.',
            ], 404);
        }

        unset($cart[$request->cart_item_id]);
        session()->put('cart', $cart);

        $totals = $this->cart->totals();

        return response()->json([
            'success' => true,
            'message' => 'Article supprimé du panier avec succès!',
            'cart_count' => $this->cart->count(),
            'subtotal' => number_format($totals['subtotal'], 2),
            'tax' => number_format($totals['tax'], 2),
            'shipping' => number_format($totals['shipping'], 2),
            'total' => number_format($totals['total'], 2),
        ]);
    }

    public function clear()
    {
        session()->forget('cart');

        return response()->json([
            'success' => true,
            'message' => 'Panier vidé avec succès!',
            'cart_count' => 0,
        ]);
    }

    public function count()
    {
        return response()->json([
            'count' => $this->cart->count(),
        ]);
    }
}
