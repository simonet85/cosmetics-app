<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodsSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'payment_credit_card_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'payment',
            ],
            [
                'key' => 'payment_paypal_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'payment',
            ],
            [
                'key' => 'payment_bank_transfer_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'payment',
            ],
            [
                'key' => 'payment_cash_on_delivery_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'payment',
            ],
            [
                'key' => 'site_logo',
                'value' => '',
                'type' => 'text',
                'group' => 'general',
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
