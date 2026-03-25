<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'           => fake()->randomElement(['Básico', 'Pro', 'Premium']),
            'description'    => fake()->sentence(),
            'price'          => fake()->randomFloat(2, 49, 199),
            'cuts_per_month' => fake()->randomElement([4, 8, 12]),
            'is_active'      => true,
        ];
    }

    public function free(): static
    {
        return $this->state(fn () => ['price' => 0.00, 'name' => 'Grátis']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
