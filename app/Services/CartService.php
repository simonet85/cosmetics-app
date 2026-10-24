<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
        // An empty cart ships nothing, so it is not charged shipping
        $shipping = $lines === [] || $subtotal >= $freeShippingThreshold ? 0.0 : $standardShippingCost;

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

    /**
     * Stores the cart in the session (the working copy) and, for a signed-in customer, in the database so it
     * survives logout, another browser and an expired session.
     *
     * @param  array<string, array{product_id: int, variant_id: ?int, quantity: int}>  $cart
     */
    public function save(array $cart): void
    {
        if ($cart === []) {
            session()->forget('cart');
        } else {
            session()->put('cart', $cart);
        }

        if ($user = auth()->user()) {
            $this->persist($user, $cart);
        }
    }

    public function clear(): void
    {
        $this->save([]);
    }

    /**
     * On sign-in: what the customer saved earlier and what is in this browser's cart are combined.
     * A line present on both sides takes the larger quantity (the same cart seen twice must not double),
     * capped by the stock available; products that are gone are dropped.
     */
    public function mergeForUser(User $user): void
    {
        $merged = [];

        foreach (Cart::where('user_id', $user->id)->get() as $row) {
            $merged[$this->key($row->product_id, $row->variant_id)] = [
                'product_id' => (int) $row->product_id,
                'variant_id' => $row->variant_id ? (int) $row->variant_id : null,
                'quantity' => (int) $row->quantity,
            ];
        }

        foreach (session()->get('cart', []) as $item) {
            // A line in an old or damaged shape must not break the sign-in: it is simply not merged
            if (! is_array($item) || empty($item['product_id']) || (int) ($item['quantity'] ?? 0) < 1) {
                continue;
            }

            $key = $this->key($item['product_id'], $item['variant_id'] ?? null);

            $merged[$key] = [
                'product_id' => (int) $item['product_id'],
                'variant_id' => ! empty($item['variant_id']) ? (int) $item['variant_id'] : null,
                'quantity' => max((int) $item['quantity'], $merged[$key]['quantity'] ?? 0),
            ];
        }

        $kept = [];
        foreach ($merged as $key => $item) {
            $product = Product::active()->find($item['product_id']);
            if (! $product) {
                continue;
            }

            $stock = $product->stock;
            if ($item['variant_id']) {
                $variant = ProductVariant::where('product_id', $product->id)->where('is_active', true)->find($item['variant_id']);
                if (! $variant) {
                    continue;
                }
                $stock = $variant->stock;
            }

            if ($stock < 1) {
                continue;
            }

            $item['quantity'] = min($item['quantity'], $stock);
            $kept[$key] = $item;
        }

        $this->save($kept);
    }

    private function key(int|string $productId, int|string|null $variantId): string
    {
        return $productId.($variantId ? '_'.$variantId : '');
    }

    private function persist(User $user, array $cart): void
    {
        DB::transaction(function () use ($user, $cart) {
            Cart::where('user_id', $user->id)->delete();

            foreach ($cart as $item) {
                Cart::create([
                    'user_id' => $user->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                ]);
            }
        });
    }
}
