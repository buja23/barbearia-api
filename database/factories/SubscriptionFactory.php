<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'plan_id'         => PlanFactory::new(),
            'starts_at'       => now(),
            'expires_at'      => now()->addMonth(),
            'status'          => 'active',
            'uses_this_month' => 0,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status'     => 'active',
            'expires_at' => now()->subDay(),
        ]);
    }

    public function atLimit(int $limit): static
    {
        return $this->state(fn () => ['uses_this_month' => $limit]);
    }
}
