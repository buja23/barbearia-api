<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // index()
    // -------------------------------------------------------------------------

    /** @test */
    public function user_can_view_their_active_subscription(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['cuts_per_month' => 4]);
        Subscription::factory()->create(['user_id' => $user->id, 'plan_id' => $plan->id]);

        $this->actingAs($user)
            ->getJson('/api/user/subscription')
            ->assertStatus(200)
            ->assertJsonStructure(['id', 'status', 'uses_this_month', 'plan']);
    }

    /** @test */
    public function returns_404_when_no_subscription(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/user/subscription')
            ->assertStatus(404);
    }

    /** @test */
    public function expired_subscription_returns_404(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        Subscription::factory()->expired()->create(['user_id' => $user->id, 'plan_id' => $plan->id]);

        $this->actingAs($user)
            ->getJson('/api/user/subscription')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // store()
    // -------------------------------------------------------------------------

    /** @test */
    public function user_can_subscribe_to_free_plan(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->free()->create();

        $this->actingAs($user)
            ->postJson('/api/subscribe', ['plan_id' => $plan->id])
            ->assertStatus(201)
            ->assertJsonPath('subscription.status', 'active');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status'  => 'active',
        ]);
    }

    /** @test */
    public function user_cannot_subscribe_twice(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->free()->create();
        Subscription::factory()->create(['user_id' => $user->id, 'plan_id' => $plan->id]);

        $this->actingAs($user)
            ->postJson('/api/subscribe', ['plan_id' => $plan->id])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Você já possui uma assinatura ativa.');
    }

    /** @test */
    public function subscribe_fails_with_inactive_plan(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->inactive()->create();

        $this->actingAs($user)
            ->postJson('/api/subscribe', ['plan_id' => $plan->id])
            ->assertStatus(404); // Plan not found by is_active filter
    }

    /** @test */
    public function subscribe_fails_with_invalid_plan_id(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/subscribe', ['plan_id' => 99999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['plan_id']);
    }

    /** @test */
    public function unauthenticated_user_cannot_subscribe(): void
    {
        $plan = Plan::factory()->free()->create();

        $this->postJson('/api/subscribe', ['plan_id' => $plan->id])
            ->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // destroy()
    // -------------------------------------------------------------------------

    /** @test */
    public function user_can_cancel_subscription(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        $sub  = Subscription::factory()->create(['user_id' => $user->id, 'plan_id' => $plan->id]);

        $this->actingAs($user)
            ->postJson('/api/subscribe/cancel')
            ->assertStatus(200)
            ->assertJson(['message' => 'Assinatura cancelada com sucesso.']);

        $this->assertDatabaseHas('subscriptions', [
            'id'     => $sub->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function cancel_returns_404_when_no_subscription(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/subscribe/cancel')
            ->assertStatus(404);
    }
}
