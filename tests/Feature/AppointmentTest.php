<?php

namespace Tests\Feature;

use App\Filament\Resources\AppointmentResource\Pages\ListAppointments;
use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Barbershop;
use App\Models\OpeningHour;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function appointment_price_is_zero_when_client_has_active_subscription(): void
    {
        $plan = Plan::factory()->create(['barbershop_id' => $this->barbershop->id, 'cuts_per_month' => 4, 'price' => 0]);
        Subscription::factory()->create([
            'barbershop_id'  => $this->barbershop->id,
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

    #[Test]
    public function subscription_counter_increments_after_appointment(): void
    {
        $plan = Plan::factory()->create(['barbershop_id' => $this->barbershop->id, 'cuts_per_month' => 4, 'price' => 0]);
        $sub  = Subscription::factory()->create([
            'barbershop_id'   => $this->barbershop->id,
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

    #[Test]
    public function unauthenticated_user_cannot_create_appointment(): void
    {
        $this->postJson('/api/appointments', [
            'barber_id'    => $this->barber->id,
            'service_id'   => $this->service->id,
            'scheduled_at' => $this->nextWeekday()->format('Y-m-d H:i:s'),
        ])->assertStatus(401);
    }

    #[Test]
    public function public_slots_require_the_barber_and_service_to_belong_to_the_slug(): void
    {
        $otherShop = Barbershop::factory()->create();
        $otherBarber = Barber::factory()->create(['barbershop_id' => $otherShop->id]);
        $otherService = Service::factory()->create(['barbershop_id' => $otherShop->id]);
        $query = ['date' => $this->nextWeekday()->toDateString(),
            'barber_id' => $this->barber->id, 'service_id' => $this->service->id];
        $url = '/api/'.$this->barbershop->slug.'/slots?';

        $this->getJson($url.http_build_query($query))->assertOk()->assertJsonFragment(['10:00']);
        $this->getJson('/api/nonexistent-shop/slots?'.http_build_query($query))->assertNotFound();
        $this->getJson($url.http_build_query(array_replace($query, ['barber_id' => $otherBarber->id])))
            ->assertUnprocessable()->assertJsonValidationErrors('barber_id');
        $this->getJson($url.http_build_query(array_replace($query, ['service_id' => $otherService->id])))
            ->assertUnprocessable()->assertJsonValidationErrors('service_id');
        $this->getJson($url.http_build_query(array_replace($query, [
            'barber_id' => $otherBarber->id, 'service_id' => $otherService->id,
        ])))->assertUnprocessable()->assertJsonValidationErrors(['barber_id', 'service_id']);
    }

    #[Test]
    public function appointment_rejects_a_service_from_another_shop(): void
    {
        $otherService = Service::factory()->create();
        $this->actingAs($this->client)->postJson('/api/appointments', [
            'barber_id' => $this->barber->id, 'service_id' => $otherService->id,
            'scheduled_at' => $this->nextWeekday()->format('Y-m-d H:i:s'),
        ])->assertUnprocessable()->assertJsonValidationErrors('service_id');
        $this->assertDatabaseCount('appointments', 0);
    }

    #[Test]
    public function unavailable_hours_are_rejected_before_saving(): void
    {
        $this->barber->update(['lunch_start' => '12:00:00', 'lunch_end' => '13:00:00']);
        foreach ([
            $this->nextWeekday(Carbon::SUNDAY),
            $this->nextWeekday()->setTime(8, 0),
            $this->nextWeekday()->setTime(18, 45),
            $this->nextWeekday()->setTime(12, 0),
            $this->nextWeekday()->setTime(10, 0, 30),
        ] as $start) {
            $this->actingAs($this->client)->postJson('/api/appointments', [
                'barber_id' => $this->barber->id, 'service_id' => $this->service->id,
                'scheduled_at' => $start->format('Y-m-d H:i:s'),
            ])->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');
        }
        $this->assertDatabaseCount('appointments', 0);
    }

    #[Test]
    public function conflicting_booking_is_rejected_but_adjacent_slot_remains_available(): void
    {
        $start = $this->nextWeekday();
        Appointment::factory()->create([
            'barber_id' => $this->barber->id, 'service_id' => $this->service->id,
            'barbershop_id' => $this->barbershop->id,
            'scheduled_at' => $start, 'end_at' => $start->copy()->addHour(), 'status' => 'confirmed',
        ]);

        // Persisted end_at is authoritative even if the service now lasts only 30 minutes.
        $this->actingAs($this->client)->postJson('/api/appointments', [
            'barber_id' => $this->barber->id, 'service_id' => $this->service->id,
            'scheduled_at' => $start->copy()->addMinutes(45)->format('Y-m-d H:i:s'),
        ])->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');
        $this->assertDatabaseCount('appointments', 1);

        $this->postJson('/api/appointments', [
            'barber_id' => $this->barber->id, 'service_id' => $this->service->id,
            'scheduled_at' => $start->copy()->addHour()->format('Y-m-d H:i:s'),
        ])->assertCreated();
    }

    #[Test]
    public function booking_does_not_consume_a_subscription_from_another_shop(): void
    {
        $plan = Plan::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $this->client->id, 'plan_id' => $plan->id,
            'barbershop_id' => $plan->barbershop_id, 'uses_this_month' => 0,
        ]);
        $this->actingAs($this->client)->postJson('/api/appointments', [
            'barber_id' => $this->barber->id, 'service_id' => $this->service->id,
            'scheduled_at' => $this->nextWeekday()->format('Y-m-d H:i:s'),
        ])->assertCreated();
        $this->assertDatabaseHas('appointments', ['user_id' => $this->client->id, 'total_price' => 50]);
        $this->assertEquals(0, $subscription->fresh()->uses_this_month);
    }

    #[Test]
    public function filament_search_matches_manual_and_related_client_names_without_leaking_tenants(): void
    {
        $attributes = ['barber_id' => $this->barber->id, 'service_id' => $this->service->id,
            'barbershop_id' => $this->barbershop->id, 'status' => 'confirmed'];
        $manual = Appointment::factory()->create(array_merge($attributes, [
            'client_name' => 'Cliente Manual', 'user_id' => null,
        ]));
        $this->client->update(['name' => 'Cliente Vinculado']);
        $linked = Appointment::factory()->create(array_merge($attributes, [
            'client_name' => null, 'user_id' => $this->client->id,
        ]));
        $outside = Appointment::factory()->create(['client_name' => 'Cliente Manual', 'status' => 'confirmed']);

        $this->actingAs($this->barbershop->user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::setTenant($this->barbershop);

        Livewire::test(ListAppointments::class)
            ->searchTable('Cliente Manual')
            ->assertCanSeeTableRecords([$manual])
            ->assertCanNotSeeTableRecords([$linked, $outside])
            ->searchTable('Cliente Vinculado')
            ->assertCanSeeTableRecords([$linked])
            ->assertCanNotSeeTableRecords([$manual, $outside]);
    }

    // -------------------------------------------------------------------------
    // index()
    // -------------------------------------------------------------------------

    #[Test]
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

    #[Test]
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
            'status' => 'canceled',
        ]);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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
