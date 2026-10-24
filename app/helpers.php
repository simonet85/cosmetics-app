<?php

use Illuminate\Support\Facades\DB;

if (! function_exists('getSetting')) {
    /**
     * Get a setting value from the database
     *
     * @param  string  $key  The setting key
     * @param  mixed  $default  The default value if setting doesn't exist
     * @return mixed
     */
    function getSetting(string $key, $default = null)
    {
        $setting = DB::table('settings')->where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        // Handle boolean type
        if ($setting->type === 'boolean') {
            return $setting->value;
        }

        // Handle other types
        return $setting->value ?? $default;
    }
}

if (! function_exists('getPaymentMethodLabel')) {
    /**
     * Get the French label for a payment method
     *
     * @param  string  $method  The payment method code
     * @return string The French label
     */
    function getPaymentMethodLabel(string $method): string
    {
        $labels = [
            'credit_card' => 'Carte de crédit',
            'paypal' => 'PayPal',
            'bank_transfer' => 'Virement bancaire',
            'cash_on_delivery' => 'Paiement à la livraison',
        ];

        return $labels[$method] ?? ucfirst(str_replace('_', ' ', $method));
    }
}

if (! function_exists('format_fcfa')) {
    /**
     * Format an amount as French-style FCFA: whole francs, non-breaking spaces ("12 500 FCFA").
     * The CFA franc has no subunit, so any decimals are rounded away.
     */
    function format_fcfa(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 0, ',', "\u{00A0}")."\u{00A0}FCFA";
    }
}
