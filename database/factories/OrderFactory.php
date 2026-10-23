<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(5000, 60000);
        $address = [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'city' => 'Abidjan',
            'quartier' => 'Cocody',
            'phone' => fake()->numerify('07########'),
        ];

        return [
            'user_id' => User::factory(),
            'order_number' => 'ORD-'.strtoupper(fake()->unique()->bothify('########')),
            'subtotal' => $subtotal,
            'shipping' => 0,
            'tax' => 0,
            'discount' => 0,
            'total' => $subtotal,
            'status' => 'pending',
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
            'shipping_address' => $address,
            'billing_address' => $address,
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->numerify('07########'),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid',
            'status' => 'processing',
        ]);
    }

    public function moneyFusion(string $token): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'moneyfusion',
            'moneyfusion_token' => $token,
        ]);
    }
}
