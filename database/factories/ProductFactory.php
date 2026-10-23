<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-??')),
            'price' => fake()->numberBetween(2000, 40000),
            'stock' => 10,
            'short_description' => fake()->sentence(),
            'full_description' => fake()->paragraph(),
            'is_active' => true,
            'is_archived' => false,
        ];
    }

    public function discounted(float $discountPrice): static
    {
        return $this->state(fn (array $attributes) => ['discount_price' => $discountPrice]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => ['stock' => 0]);
    }
}
