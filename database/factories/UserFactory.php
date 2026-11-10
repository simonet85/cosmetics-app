<?php

namespace Database\Factories;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->numerify('07########'),
            'city' => 'Abidjan',
            'role' => 'customer',
            'is_active' => true,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Admin access is granted by the Spatie role (User::isAdmin()), not by the role column alone.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'admin'])
            ->afterCreating(fn ($user) => $user->assignRole(Role::findOrCreate('admin')));
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * A customer who proved a phone number and can sign in with a one-time code.
     */
    public function smsEnabled(?string $phone = null): static
    {
        $phone ??= fake()->unique()->numerify('07########');

        return $this->state(fn () => ['phone' => $phone])
            ->afterCreating(fn (User $user) => $user->forceFill([
                'phone_normalized' => PhoneNumber::normalize($phone),
                'phone_verified_at' => now(),
            ])->save());
    }
}
