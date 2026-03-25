<?php

namespace Database\Factories;

use App\Models\Barbershop;
use Illuminate\Database\Eloquent\Factories\Factory;

class OpeningHourFactory extends Factory
{
    public function definition(): array
    {
        return [
            'barbershop_id' => BarbershopFactory::new(),
            'day_of_week'   => fake()->numberBetween(0, 6),
            'opening_time'  => '09:00:00',
            'closing_time'  => '19:00:00',
            'is_closed'     => false,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => ['is_closed' => true]);
    }

    /**
     * Creates opening hours for all weekdays (Mon–Fri) for a given barbershop.
     */
    public static function weekdays(int $barbershopId): void
    {
        foreach (range(1, 5) as $day) {
            \App\Models\OpeningHour::factory()->create([
                'barbershop_id' => $barbershopId,
                'day_of_week'   => $day,
            ]);
        }
    }
}
