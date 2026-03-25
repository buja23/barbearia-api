<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BarbershopFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company() . ' Barbearia';

        return [
            'user_id'               => User::factory()->barber(),
            'name'                  => $name,
            'slug'                  => Str::slug($name) . '-' . fake()->unique()->numberBetween(1000, 9999),
            'phone'                 => fake()->phoneNumber(),
            'address'               => fake()->address(),
            'subscription_status'   => 'trial',
            'trial_ends_at'         => now()->addDays(14),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'subscription_status'      => 'active',
            'subscription_expires_at'  => now()->addMonth(),
        ]);
    }
}
