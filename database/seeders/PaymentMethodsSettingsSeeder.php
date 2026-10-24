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
                // Off: no payment gateway is wired to it, orders would stay unpaid
                'value' => '0',
                'type' => 'boolean',
                'group' => 'payment',
            ],
            [
                'key' => 'payment_paypal_enabled',
                // Off: no payment gateway is wired to it, orders would stay unpaid
                'value' => '0',
                'type' => 'boolean',
                'group' => 'payment',
            ],
            [
                'key' => 'payment_bank_transfer_enabled',
                // Off until the shop's bank details exist and are sent to the customer (none are stored today)
                'value' => '0',
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
