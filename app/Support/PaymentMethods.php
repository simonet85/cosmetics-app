<?php

namespace App\Support;

/**
 * Which payment methods the shop currently accepts, read from the settings the admin controls.
 * Single source of truth for the checkout (enforcement) and the storefront (what is advertised).
 */
class PaymentMethods
{
    /** @var array<string, string> */
    public const LABELS = [
        'moneyfusion' => 'Mobile money',
        'cash_on_delivery' => 'Paiement à la livraison',
        'bank_transfer' => 'Virement bancaire',
        'credit_card' => 'Carte bancaire',
        'paypal' => 'PayPal',
    ];

    /** @return array<string, string> method => label in the current language */
    public static function labels(): array
    {
        return array_map(fn ($label) => __($label), self::LABELS);
    }

    public static function label(?string $method): string
    {
        return isset(self::LABELS[$method]) ? __(self::LABELS[$method]) : (string) $method;
    }

    /** @return array<string, bool> */
    public static function enabled(): array
    {
        $enabled = [];

        foreach (array_keys(self::LABELS) as $method) {
            $enabled[$method] = getSetting("payment_{$method}_enabled", '1') === '1';
        }

        return $enabled;
    }

    /** @return array<string, string> method => label, only the accepted ones */
    public static function acceptedLabels(): array
    {
        return array_intersect_key(self::labels(), array_filter(self::enabled()));
    }
}
