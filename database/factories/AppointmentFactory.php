<?php

namespace Database\Factories;

use App\Models\Barber;
use App\Models\Barbershop;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        $scheduled = now()->addDays(fake()->numberBetween(1, 30))->setTime(10, 0);

        return [
            'barbershop_id' => BarbershopFactory::new(),
            'barber_id'     => BarberFactory::new(),
            'service_id'    => ServiceFactory::new(),
            'user_id'       => User::factory(),
            'client_name'   => fake()->name(),
            'client_phone'  => '11999999999',
            'scheduled_at'  => $scheduled,
            'end_at'        => (clone $scheduled)->addMinutes(30),
            'total_price'   => 50.00,
            'status'        => 'confirmed',
            'payment_status' => 'pending',
            'payment_method' => 'pix',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'canceled']);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed']);
    }
}
