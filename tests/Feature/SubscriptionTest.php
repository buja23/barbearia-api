<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // index()
    // -------------------------------------------------------------------------

    #[Test]
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

    #[Test]
    public function returns_404_when_no_subscription(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/user/subscription')
            ->assertStatus(404);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function subscribe_fails_with_inactive_plan(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->inactive()->create();

        $this->actingAs($user)
            ->postJson('/api/subscribe', ['plan_id' => $plan->id])
            ->assertStatus(422); // Plan fails validation (is_active = false)
    }

    #[Test]
    public function subscribe_fails_with_invalid_plan_id(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/subscribe', ['plan_id' => 99999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['plan_id']);
    }

    #[Test]
    public function unauthenticated_user_cannot_subscribe(): void
    {
        $plan = Plan::factory()->free()->create();

        $this->postJson('/api/subscribe', ['plan_id' => $plan->id])
            ->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // destroy()
    // -------------------------------------------------------------------------

    #[Test]
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
            'status' => 'canceled',
        ]);
    }

    #[Test]
    public function cancel_returns_404_when_no_subscription(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/subscribe/cancel')
            ->assertStatus(404);
    }
}
