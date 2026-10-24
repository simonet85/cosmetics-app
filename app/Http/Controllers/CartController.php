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

        return view('storefront.cart', compact('cartItems', 'subtotal', 'tax', 'shipping', 'total'));
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

        if (! $request->variant_id && $product->variants()->where('is_active', true)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Choisissez une option avant d\'ajouter ce produit.',
            ], 422);
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

            $available = isset($variant) ? $variant->stock : $product->stock;

            if ($available < $newQuantity) {
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

        $this->cart->save($cart);

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

        $item = $cart[$request->cart_item_id];
        $product = Product::active()->find($item['product_id']);
        $variant = ! empty($item['variant_id']) ? ProductVariant::find($item['variant_id']) : null;
        $available = $variant ? $variant->stock : $product?->stock;

        if (! $product || $available < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuffisant pour cette quantité.',
            ], 400);
        }

        $cart[$request->cart_item_id]['quantity'] = (int) $request->quantity;
        $this->cart->save($cart);

        $lines = $this->cart->lines();
        $totals = $this->cart->totals($lines);

        return response()->json([
            'success' => true,
            'message' => 'Panier mis à jour avec succès!',
            'cart_count' => $this->cart->count(),
            'item_subtotal' => round($lines[$request->cart_item_id]['subtotal'] ?? 0),
            ...$this->totalsPayload($totals),
            'formatted' => [
                'item_subtotal' => format_fcfa($lines[$request->cart_item_id]['subtotal'] ?? 0),
                ...$this->formattedTotals($totals),
            ],
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
        $this->cart->save($cart);

        $totals = $this->cart->totals();

        return response()->json([
            'success' => true,
            'message' => 'Article supprimé du panier avec succès!',
            'cart_count' => $this->cart->count(),
            ...$this->totalsPayload($totals),
            'formatted' => $this->formattedTotals($totals),
        ]);
    }

    public function clear()
    {
        $this->cart->clear();

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

    /** @param  array{subtotal: float, tax: float, shipping: float, total: float}  $totals */
    private function totalsPayload(array $totals): array
    {
        return [
            'subtotal' => round($totals['subtotal']),
            'tax' => round($totals['tax']),
            'shipping' => round($totals['shipping']),
            'total' => round($totals['total']),
            'free_shipping_remaining' => max(0, round((float) getSetting('free_shipping_threshold', '50000') - $totals['subtotal'])),
        ];
    }

    /** @param  array{subtotal: float, tax: float, shipping: float, total: float}  $totals */
    private function formattedTotals(array $totals): array
    {
        return [
            'subtotal' => format_fcfa($totals['subtotal']),
            'tax' => format_fcfa($totals['tax']),
            'shipping' => $totals['shipping'] > 0 ? format_fcfa($totals['shipping']) : 'Gratuite',
            'total' => format_fcfa($totals['total']),
        ];
    }
}
