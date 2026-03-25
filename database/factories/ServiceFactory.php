<?php

namespace Database\Factories;

use App\Models\Barbershop;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'barbershop_id'    => BarbershopFactory::new(),
            'name'             => fake()->randomElement(['Corte', 'Barba', 'Corte + Barba', 'Pigmentação', 'Sobrancelha']),
            'price'            => fake()->randomFloat(2, 20, 120),
            'duration_minutes' => fake()->randomElement([30, 45, 60]),
            'is_active'        => true,
        ];
    }
}
