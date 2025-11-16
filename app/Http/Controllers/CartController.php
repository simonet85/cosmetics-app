<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cart = session()->get('cart', []);
        $cartItems = [];
        $subtotal = 0;

        foreach ($cart as $id => $item) {
            $product = Product::with('images')->find($item['product_id']);
            if ($product) {
                $cartItem = [
                    'id' => $id,
                    'product' => $product,
                    'variant' => isset($item['variant_id']) ? ProductVariant::find($item['variant_id']) : null,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['price'] * $item['quantity']
                ];
                $cartItems[] = $cartItem;
                $subtotal += $cartItem['subtotal'];
            }
        }

        // Get tax rate from settings (convert percentage to decimal)
        $taxRate = floatval(getSetting('tax_rate', '15')) / 100;
        $tax = $subtotal * $taxRate;

        // Get shipping settings
        $standardShippingCost = floatval(getSetting('standard_shipping_cost', '0'));
        $freeShippingThreshold = floatval(getSetting('free_shipping_threshold', '50000'));
        $shipping = $subtotal >= $freeShippingThreshold ? 0 : $standardShippingCost;

        $total = $subtotal + $tax + $shipping;

        return view('cart.index', compact('cartItems', 'subtotal', 'tax', 'shipping', 'total'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'variant_id' => 'nullable|exists:product_variants,id'
        ]);

        $product = Product::findOrFail($request->product_id);

        // Check stock
        if ($product->stock < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuffisant pour ce produit.'
            ], 400);
        }

        // Get price (variant price or product price)
        $price = $product->price;
        if ($request->variant_id) {
            $variant = ProductVariant::findOrFail($request->variant_id);
            $price = $variant->price;
        }

        $cart = session()->get('cart', []);

        // Create unique cart item ID
        $cartItemId = $request->product_id . ($request->variant_id ? '_' . $request->variant_id : '');

        // If item already in cart, update quantity
        if (isset($cart[$cartItemId])) {
            $newQuantity = $cart[$cartItemId]['quantity'] + $request->quantity;

            if ($product->stock < $newQuantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuffisant pour cette quantité.'
                ], 400);
            }

            $cart[$cartItemId]['quantity'] = $newQuantity;
        } else {
            // Add new item to cart
            $cart[$cartItemId] = [
                'product_id' => $request->product_id,
                'variant_id' => $request->variant_id,
                'quantity' => $request->quantity,
                'price' => $price
            ];
        }

        session()->put('cart', $cart);

        $cartCount = array_sum(array_column($cart, 'quantity'));

        return response()->json([
            'success' => true,
            'message' => 'Produit ajouté au panier avec succès!',
            'cart_count' => $cartCount
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'cart_item_id' => 'required|string',
            'quantity' => 'required|integer|min:1'
        ]);

        $cart = session()->get('cart', []);

        if (!isset($cart[$request->cart_item_id])) {
            return response()->json([
                'success' => false,
                'message' => 'Article non trouvé dans le panier.'
            ], 404);
        }

        $product = Product::find($cart[$request->cart_item_id]['product_id']);

        if ($product->stock < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuffisant pour cette quantité.'
            ], 400);
        }

        $cart[$request->cart_item_id]['quantity'] = $request->quantity;
        session()->put('cart', $cart);

        $itemSubtotal = $cart[$request->cart_item_id]['price'] * $request->quantity;
        $cartCount = array_sum(array_column($cart, 'quantity'));

        // Calculate totals
        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        // Get tax rate from settings (convert percentage to decimal)
        $taxRate = floatval(getSetting('tax_rate', '15')) / 100;
        $tax = $subtotal * $taxRate;

        // Get shipping settings
        $standardShippingCost = floatval(getSetting('standard_shipping_cost', '0'));
        $freeShippingThreshold = floatval(getSetting('free_shipping_threshold', '50000'));
        $shipping = $subtotal >= $freeShippingThreshold ? 0 : $standardShippingCost;

        $total = $subtotal + $tax + $shipping;

        return response()->json([
            'success' => true,
            'message' => 'Panier mis à jour avec succès!',
            'cart_count' => $cartCount,
            'item_subtotal' => number_format($itemSubtotal, 2),
            'subtotal' => number_format($subtotal, 2),
            'tax' => number_format($tax, 2),
            'shipping' => number_format($shipping, 2),
            'total' => number_format($total, 2)
        ]);
    }

    public function remove(Request $request)
    {
        $request->validate([
            'cart_item_id' => 'required|string'
        ]);

        $cart = session()->get('cart', []);

        if (!isset($cart[$request->cart_item_id])) {
            return response()->json([
                'success' => false,
                'message' => 'Article non trouvé dans le panier.'
            ], 404);
        }

        unset($cart[$request->cart_item_id]);
        session()->put('cart', $cart);

        $cartCount = array_sum(array_column($cart, 'quantity'));

        // Calculate totals
        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        // Get tax rate from settings (convert percentage to decimal)
        $taxRate = floatval(getSetting('tax_rate', '15')) / 100;
        $tax = $subtotal * $taxRate;

        // Get shipping settings
        $standardShippingCost = floatval(getSetting('standard_shipping_cost', '0'));
        $freeShippingThreshold = floatval(getSetting('free_shipping_threshold', '50000'));
        $shipping = $subtotal >= $freeShippingThreshold ? 0 : $standardShippingCost;

        $total = $subtotal + $tax + $shipping;

        return response()->json([
            'success' => true,
            'message' => 'Article supprimé du panier avec succès!',
            'cart_count' => $cartCount,
            'subtotal' => number_format($subtotal, 2),
            'tax' => number_format($tax, 2),
            'shipping' => number_format($shipping, 2),
            'total' => number_format($total, 2)
        ]);
    }

    public function clear()
    {
        session()->forget('cart');

        return response()->json([
            'success' => true,
            'message' => 'Panier vidé avec succès!',
            'cart_count' => 0
        ]);
    }

    public function count()
    {
        $cart = session()->get('cart', []);
        $count = array_sum(array_column($cart, 'quantity'));

        return response()->json([
            'count' => $count
        ]);
    }
}
