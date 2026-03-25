<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Barbershop;
use App\Models\OpeningHour;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private Barbershop $barbershop;
    private Barber $barber;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $owner             = User::factory()->barber()->create();
        $this->barbershop  = Barbershop::factory()->active()->create(['user_id' => $owner->id]);
        $this->barber      = Barber::factory()->noLunch()->create(['barbershop_id' => $this->barbershop->id]);
        $this->service     = Service::factory()->create([
            'barbershop_id'    => $this->barbershop->id,
            'duration_minutes' => 30,
            'price'            => 50.00,
        ]);
        $this->client = User::factory()->create();

        // Open Monday–Friday
        foreach (range(1, 5) as $day) {
            OpeningHour::factory()->create([
                'barbershop_id' => $this->barbershop->id,
                'day_of_week'   => $day,
                'opening_time'  => '09:00:00',
                'closing_time'  => '19:00:00',
            ]);
        }
    }

    private function nextWeekday(int $carbonDay = Carbon::MONDAY): Carbon
    {
        return Carbon::now()->next($carbonDay)->setTime(10, 0, 0);
    }

    // -------------------------------------------------------------------------
    // store()
    // -------------------------------------------------------------------------

    /** @test */
    public function client_can_create_appointment(): void
    {
        $scheduledAt = $this->nextWeekday()->format('Y-m-d H:i:s');

        $this->actingAs($this->client)
            ->postJson('/api/appointments', [
                'barber_id'    => $this->barber->id,
                'service_id'   => $this->service->id,
                'scheduled_at' => $scheduledAt,
            ])->assertStatus(201)
                ->assertJsonStructure(['message', 'appointment']);

        $this->assertDatabaseHas('appointments', [
            'user_id'       => $this->client->id,
            'barber_id'     => $this->barber->id,
            'barbershop_id' => $this->barbershop->id,
            'status'        => 'confirmed',
        ]);
    }

    /** @test */
    public function appointment_in_the_past_is_rejected(): void
    {
        $this->actingAs($this->client)
            ->postJson('/api/appointments', [
                'barber_id'    => $this->barber->id,
                'service_id'   => $this->service->id,
                'scheduled_at' => now()->subHour()->format('Y-m-d H:i:s'),
            ])->assertStatus(422)
                ->assertJsonValidationErrors(['scheduled_at']);
    }

    /** @test */
    public function appointment_more_than_one_year_ahead_is_rejected(): void
    {
        $this->actingAs($this->client)
            ->postJson('/api/appointments', [
                'barber_id'    => $this->barber->id,
                'service_id'   => $this->service->id,
                'scheduled_at' => now()->addYears(2)->format('Y-m-d H:i:s'),
            ])->assertStatus(422)
                ->assertJsonValidationErrors(['scheduled_at']);
    }

    /** @test */
    public function appointment_fails_with_invalid_barber(): void
    {
        $this->actingAs($this->client)
            ->postJson('/api/appointments', [
                'barber_id'    => 99999,
                'service_id'   => $this->service->id,
                'scheduled_at' => $this->nextWeekday()->format('Y-m-d H:i:s'),
            ])->assertStatus(422)
                ->assertJsonValidationErrors(['barber_id']);
    }

    /** @test */
    public function appointment_price_is_zero_when_client_has_active_subscription(): void
    {
        $plan = Plan::factory()->create(['cuts_per_month' => 4, 'price' => 0]);
        Subscription::factory()->create([
            'user_id'        => $this->client->id,
            'plan_id'        => $plan->id,
            'uses_this_month' => 0,
        ]);

        $this->actingAs($this->client)
            ->postJson('/api/appointments', [
                'barber_id'    => $this->barber->id,
                'service_id'   => $this->service->id,
                'scheduled_at' => $this->nextWeekday()->format('Y-m-d H:i:s'),
            ])->assertStatus(201);

        $this->assertDatabaseHas('appointments', [
            'user_id'     => $this->client->id,
            'total_price' => 0.00,
        ]);
    }

    /** @test */
    public function subscription_counter_increments_after_appointment(): void
    {
        $plan = Plan::factory()->create(['cuts_per_month' => 4, 'price' => 0]);
        $sub  = Subscription::factory()->create([
            'user_id'         => $this->client->id,
            'plan_id'         => $plan->id,
            'uses_this_month' => 0,
        ]);

        $this->actingAs($this->client)
            ->postJson('/api/appointments', [
                'barber_id'    => $this->barber->id,
                'service_id'   => $this->service->id,
                'scheduled_at' => $this->nextWeekday()->format('Y-m-d H:i:s'),
            ])->assertStatus(201);

        $this->assertDatabaseHas('subscriptions', [
            'id'              => $sub->id,
            'uses_this_month' => 1,
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_create_appointment(): void
    {
        $this->postJson('/api/appointments', [
            'barber_id'    => $this->barber->id,
            'service_id'   => $this->service->id,
            'scheduled_at' => $this->nextWeekday()->format('Y-m-d H:i:s'),
        ])->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // index()
    // -------------------------------------------------------------------------

    /** @test */
    public function user_can_only_list_their_own_appointments(): void
    {
        $otherClient = User::factory()->create();

        Appointment::factory()->create([
            'user_id'       => $this->client->id,
            'barber_id'     => $this->barber->id,
            'service_id'    => $this->service->id,
            'barbershop_id' => $this->barbershop->id,
        ]);

        Appointment::factory()->create([
            'user_id'       => $otherClient->id,
            'barber_id'     => $this->barber->id,
            'service_id'    => $this->service->id,
            'barbershop_id' => $this->barbershop->id,
        ]);

        $response = $this->actingAs($this->client)
            ->getJson('/api/appointments')
            ->assertStatus(200);

        $this->assertCount(1, $response->json());
        $this->assertEquals($this->client->id, $response->json('0.user_id'));
    }

    // -------------------------------------------------------------------------
    // destroy()
    // -------------------------------------------------------------------------

    /** @test */
    public function user_can_cancel_their_own_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'user_id'       => $this->client->id,
            'barber_id'     => $this->barber->id,
            'service_id'    => $this->service->id,
            'barbershop_id' => $this->barbershop->id,
            'status'        => 'confirmed',
        ]);

        $this->actingAs($this->client)
            ->deleteJson("/api/appointments/{$appointment->id}")
            ->assertStatus(200)
            ->assertJson(['message' => 'Agendamento cancelado.']);

        $this->assertDatabaseHas('appointments', [
            'id'     => $appointment->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function user_cannot_cancel_another_users_appointment(): void
    {
        $otherClient = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'user_id'       => $otherClient->id,
            'barber_id'     => $this->barber->id,
            'service_id'    => $this->service->id,
            'barbershop_id' => $this->barbershop->id,
        ]);

        $this->actingAs($this->client)
            ->deleteJson("/api/appointments/{$appointment->id}")
            ->assertStatus(403);
    }

    /** @test */
    public function cancelling_already_cancelled_appointment_returns_422(): void
    {
        $appointment = Appointment::factory()->cancelled()->create([
            'user_id'       => $this->client->id,
            'barber_id'     => $this->barber->id,
            'service_id'    => $this->service->id,
            'barbershop_id' => $this->barbershop->id,
        ]);

        $this->actingAs($this->client)
            ->deleteJson("/api/appointments/{$appointment->id}")
            ->assertStatus(422);
    }

    /** @test */
    public function cancelling_subscription_appointment_decrements_counter(): void
    {
        $plan = Plan::factory()->create(['cuts_per_month' => 4, 'price' => 0]);
        $sub  = Subscription::factory()->create([
            'user_id'         => $this->client->id,
            'plan_id'         => $plan->id,
            'uses_this_month' => 1,
        ]);

        $appointment = Appointment::factory()->create([
            'user_id'       => $this->client->id,
            'barber_id'     => $this->barber->id,
            'service_id'    => $this->service->id,
            'barbershop_id' => $this->barbershop->id,
            'total_price'   => 0.00,
            'notes'         => 'Pago pelo plano Básico',
            'status'        => 'confirmed',
        ]);

        $this->actingAs($this->client)
            ->deleteJson("/api/appointments/{$appointment->id}")
            ->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'id'              => $sub->id,
            'uses_this_month' => 0,
        ]);
    }
}
