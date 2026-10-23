<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('PROMO-####')),
            'type' => 'percentage',
            'value' => 10,
            'is_active' => true,
            'used_count' => 0,
        ];
    }

    public function fixed(float $value): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'fixed', 'value' => $value]);
    }
}
