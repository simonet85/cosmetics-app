<?php

use Illuminate\Support\Facades\DB;

if (!function_exists('getSetting')) {
    /**
     * Get a setting value from the database
     *
     * @param string $key The setting key
     * @param mixed $default The default value if setting doesn't exist
     * @return mixed
     */
    function getSetting(string $key, $default = null)
    {
        $setting = DB::table('settings')->where('key', $key)->first();

        if (!$setting) {
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

if (!function_exists('getPaymentMethodLabel')) {
    /**
     * Get the French label for a payment method
     *
     * @param string $method The payment method code
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
