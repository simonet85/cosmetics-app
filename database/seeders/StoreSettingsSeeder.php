<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'site_name',
                'value' => 'Glowing Cosmetics',
                'type' => 'text',
                'group' => 'general',
            ],
            [
                'key' => 'site_description',
                'value' => 'Produits cosmétiques naturels et biologiques',
                'type' => 'text',
                'group' => 'general',
            ],
            [
                'key' => 'contact_email',
                'value' => 'contact@glowing-cosmetics.com',
                'type' => 'text',
                'group' => 'general',
            ],
            [
                'key' => 'phone',
                'value' => '+33 1 23 45 67 89',
                'type' => 'text',
                'group' => 'general',
            ],

            // Store Settings
            [
                'key' => 'currency',
                'value' => 'FCFA',
                'type' => 'text',
                'group' => 'store',
            ],
            [
                'key' => 'tax_rate',
                'value' => '15',
                'type' => 'number',
                'group' => 'store',
            ],
            [
                'key' => 'low_stock_threshold',
                'value' => '10',
                'type' => 'number',
                'group' => 'store',
            ],
            [
                'key' => 'enable_reviews',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'store',
            ],
            [
                'key' => 'enable_wishlist',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'store',
            ],

            // Email Settings
            [
                'key' => 'from_email',
                'value' => 'noreply@glowing-cosmetics.com',
                'type' => 'text',
                'group' => 'email',
            ],
            [
                'key' => 'from_name',
                'value' => 'Glowing Cosmetics',
                'type' => 'text',
                'group' => 'email',
            ],
            [
                'key' => 'send_order_confirmation',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'email',
            ],
            [
                'key' => 'send_shipping_notification',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'email',
            ],

            // Shipping Settings
            [
                'key' => 'standard_shipping_cost',
                'value' => '0',
                'type' => 'number',
                'group' => 'shipping',
            ],
            [
                'key' => 'free_shipping_threshold',
                'value' => '50000',
                'type' => 'number',
                'group' => 'shipping',
            ],
            [
                'key' => 'estimated_delivery_days',
                'value' => '3-5',
                'type' => 'text',
                'group' => 'shipping',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting + ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
