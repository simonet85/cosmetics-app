<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;

/**
 * Reads the session cart and prices it from the database.
 *
 * The session only remembers what was chosen (product, variant, quantity). Prices are always
 * recomputed here, so a price or discount change after "add to cart" is reflected in the cart
 * and at checkout, and a product that was deactivated or deleted in the meantime drops out.
 */
class CartService
{
    /**
     * @return array<string, array{id: string, product: Product, variant: ?ProductVariant, quantity: int, price: float, subtotal: float}>
     */
    public function lines(): array
    {
        $lines = [];

        foreach (session()->get('cart', []) as $id => $item) {
            $product = Product::with('images')->active()->find($item['product_id'] ?? null);

            if (! $product) {
                continue;
            }

            $variant = null;
            if (! empty($item['variant_id'])) {
                $variant = ProductVariant::where('product_id', $product->id)->where('is_active', true)->find($item['variant_id']);

                if (! $variant) {
                    continue;
                }
            }

            $quantity = (int) $item['quantity'];
            $price = (float) $product->final_price + (float) ($variant?->price_modifier ?? 0);

            $lines[$id] = [
                'id' => (string) $id,
                'product' => $product,
                'variant' => $variant,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $price * $quantity,
            ];
        }

        return $lines;
    }

    /**
     * @param  array<string, array{subtotal: float}>|null  $lines
     * @return array{subtotal: float, tax: float, shipping: float, total: float} total is rounded to whole francs
     */
    public function totals(?array $lines = null): array
    {
        $lines ??= $this->lines();

        $subtotal = (float) array_sum(array_column($lines, 'subtotal'));

        // Tax rate is a percentage in the settings
        $tax = $subtotal * (floatval(getSetting('tax_rate', '15')) / 100);

        $standardShippingCost = floatval(getSetting('standard_shipping_cost', '0'));
        $freeShippingThreshold = floatval(getSetting('free_shipping_threshold', '50000'));
        $shipping = $subtotal >= $freeShippingThreshold ? 0.0 : $standardShippingCost;

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            // The CFA franc has no subunit. The total is what the customer is charged and what the payment
            // gateway is sent and checks against, so it must be a whole number of francs everywhere.
            'total' => round($subtotal + $tax + $shipping),
        ];
    }

    public function count(): int
    {
        return (int) array_sum(array_column(session()->get('cart', []), 'quantity'));
    }
}
