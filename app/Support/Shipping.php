<?php

namespace App\Support;

/**
 * What the storefront may promise about delivery, read from the shop settings.
 *
 * When the standard shipping cost is zero every order ships free, so a "free from X" threshold would be
 * a misleading condition: the threshold only means something when there is a cost to waive.
 */
class Shipping
{
    /** The subtotal from which shipping becomes free, or null when shipping is always free. */
    public static function freeThreshold(): ?float
    {
        if (floatval(getSetting('standard_shipping_cost', '0')) <= 0) {
            return null;
        }

        return floatval(getSetting('free_shipping_threshold', '50000'));
    }

    public static function promise(): string
    {
        $threshold = self::freeThreshold();

        return $threshold === null ? 'Livraison gratuite' : 'Livraison gratuite dès '.format_fcfa($threshold);
    }
}
