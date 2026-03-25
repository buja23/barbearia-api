<?php

namespace Database\Factories;

use App\Models\Barbershop;
use Illuminate\Database\Eloquent\Factories\Factory;

class BarberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'barbershop_id'         => BarbershopFactory::new(),
            'name'                  => fake()->name(),
            'email'                 => fake()->unique()->safeEmail(),
            'phone'                 => fake()->phoneNumber(),
            'is_active'             => true,
            'commission_percentage' => 50.00,
            'lunch_start'           => '12:00:00',
            'lunch_end'             => '13:00:00',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function noLunch(): static
    {
        return $this->state(fn () => ['lunch_start' => null, 'lunch_end' => null]);
    }
}
