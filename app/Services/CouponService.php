<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Validates a coupon against the cart and turns it into a discount.
 *
 * The code is kept in the session between the checkout page and the order placement, but the coupon
 * row is always read again (and locked) when the order is written: a code can expire or reach its
 * usage limit while the customer fills the form.
 */
class CouponService
{
    public const SESSION_KEY = 'checkout_coupon';

    /**
     * @return array{coupon: ?Coupon, discount: float, error: ?string}
     */
    public function check(?string $code, float $subtotal): array
    {
        $code = strtoupper(trim((string) $code));

        $coupon = $code === '' ? null : Coupon::where('code', $code)->first();

        return $this->validate($coupon, $subtotal);
    }

    /**
     * Same check, with the coupon row locked until the order transaction ends, so two simultaneous
     * checkouts cannot both take the last use of a limited coupon.
     *
     * @return array{coupon: ?Coupon, discount: float, error: ?string}
     */
    public function checkLocked(?string $code, float $subtotal): array
    {
        $code = strtoupper(trim((string) $code));

        $coupon = $code === '' ? null : Coupon::where('code', $code)->lockForUpdate()->first();

        return $this->validate($coupon, $subtotal);
    }

    /**
     * The coupon remembered on the checkout page, if it is still valid for this cart.
     * An expired or no longer applicable code is dropped so the page never shows an impossible total.
     *
     * @return array{coupon: ?Coupon, discount: float, error: ?string}
     */
    public function sessionCoupon(float $subtotal): array
    {
        $code = session(self::SESSION_KEY);

        if (! $code) {
            return ['coupon' => null, 'discount' => 0.0, 'error' => null];
        }

        $result = $this->check($code, $subtotal);

        if ($result['error'] !== null) {
            session()->forget(self::SESSION_KEY);
        }

        return $result;
    }

    public function remember(Coupon $coupon): void
    {
        session([self::SESSION_KEY => $coupon->code]);
    }

    public function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function discountFor(Coupon $coupon, float $subtotal): float
    {
        $amount = $coupon->type === 'percentage'
            ? $subtotal * ((float) $coupon->value / 100)
            : (float) $coupon->value;

        if ($coupon->type === 'percentage' && $coupon->max_discount !== null && (float) $coupon->max_discount > 0) {
            $amount = min($amount, (float) $coupon->max_discount);
        }

        // The discount never makes the order negative
        return round(min($subtotal, max(0.0, $amount)));
    }

    /** Give the coupon use back when an order is cancelled (its stock is given back at the same time). */
    public function release(Order $order): void
    {
        if (! $order->coupon_code) {
            return;
        }

        $coupon = Coupon::where('code', $order->coupon_code)->first();

        if (! $coupon) {
            return;
        }

        Coupon::whereKey($coupon->id)->where('used_count', '>', 0)->decrement('used_count');
    }

    /** Take the coupon use again when a cancelled order comes back to life. */
    public function reserve(Order $order): void
    {
        if (! $order->coupon_code) {
            return;
        }

        $coupon = Coupon::where('code', $order->coupon_code)->first();

        if (! $coupon) {
            return;
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            Log::warning('Order revived after its coupon reached its usage limit', ['order_id' => $order->id, 'coupon' => $coupon->code]);
        }

        $coupon->increment('used_count');
    }

    /**
     * @return array{coupon: ?Coupon, discount: float, error: ?string}
     */
    private function validate(?Coupon $coupon, float $subtotal): array
    {
        $error = match (true) {
            $coupon === null => __('Ce code promo n\'existe pas.'),
            ! $coupon->is_active => __('Ce code promo n\'est plus actif.'),
            $coupon->start_date !== null && $coupon->start_date->startOfDay()->isFuture() => __('Ce code promo n\'est pas encore valable.'),
            $coupon->end_date !== null && $coupon->end_date->endOfDay()->isPast() => __('Ce code promo a expiré.'),
            $coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit => __('Ce code promo a atteint sa limite d\'utilisation.'),
            $coupon->min_amount !== null && $subtotal < (float) $coupon->min_amount => __('Ce code promo nécessite un panier d\'au moins :amount.', ['amount' => format_fcfa($coupon->min_amount)]),
            default => null,
        };

        if ($error !== null) {
            return ['coupon' => null, 'discount' => 0.0, 'error' => $error];
        }

        $discount = $this->discountFor($coupon, $subtotal);

        if ($discount <= 0) {
            return ['coupon' => null, 'discount' => 0.0, 'error' => __('Ce code promo n\'apporte aucune réduction à ce panier.')];
        }

        return ['coupon' => $coupon, 'discount' => $discount, 'error' => null];
    }
}
