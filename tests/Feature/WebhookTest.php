<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Barbershop;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use MercadoPago\Client\Payment\PaymentClient;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private function signedHeaders(string $body, string $secret = 'test-webhook-secret'): array
    {
        $requestId = (string) now()->timestamp;
        $data      = "{$requestId}.{$body}";
        $hash      = hash_hmac('sha256', $data, $secret);

        return [
            'x-signature'  => $hash,
            'x-request-id' => $requestId,
        ];
    }

    private function makePaymentPayload(string $paymentId): array
    {
        return [
            'type'   => 'payment',
            'action' => 'payment.updated',
            'data'   => ['id' => $paymentId],
        ];
    }

    // -------------------------------------------------------------------------
    // Signature validation
    // -------------------------------------------------------------------------

    /** @test */
    public function webhook_with_invalid_signature_is_rejected(): void
    {
        $body = json_encode(['type' => 'payment', 'data' => ['id' => '123']]);

        $this->withHeaders([
            'x-signature'  => 'invalid-hash',
            'x-request-id' => (string) now()->timestamp,
            'Content-Type' => 'application/json',
        ])->post('/api/webhooks/mercadopago', json_decode($body, true))
            ->assertStatus(403);
    }

    /** @test */
    public function webhook_without_signature_headers_is_rejected(): void
    {
        $this->postJson('/api/webhooks/mercadopago', [
            'type' => 'payment',
            'data' => ['id' => '123'],
        ])->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // Payment approval → appointment confirmed
    // -------------------------------------------------------------------------

    /** @test */
    public function approved_payment_confirms_appointment(): void
    {
        Notification::fake();

        $owner       = User::factory()->barber()->create();
        $barbershop  = Barbershop::factory()->active()->create(['user_id' => $owner->id]);
        $barber      = Barber::factory()->create(['barbershop_id' => $barbershop->id]);
        $service     = Service::factory()->create(['barbershop_id' => $barbershop->id]);
        $client      = User::factory()->create();

        $appointment = Appointment::factory()->create([
            'user_id'        => $client->id,
            'barber_id'      => $barber->id,
            'service_id'     => $service->id,
            'barbershop_id'  => $barbershop->id,
            'payment_id'     => 'mp_pay_001',
            'status'         => 'pending',
            'payment_status' => 'pending',
        ]);

        // Mock the Mercado Pago SDK response
        $fakePayment = new \stdClass();
        $fakePayment->id        = 'mp_pay_001';
        $fakePayment->status    = 'approved';

        $this->mock(PaymentClient::class, function ($mock) use ($fakePayment) {
            $mock->shouldReceive('get')->once()->andReturn($fakePayment);
        });

        $payload = $this->makePaymentPayload('mp_pay_001');
        $body    = json_encode($payload);

        $this->withHeaders(array_merge(
            $this->signedHeaders($body),
            ['Content-Type' => 'application/json']
        ))->post('/api/webhooks/mercadopago', $payload)
            ->assertStatus(200)
            ->assertJsonPath('status', 'payment_updated');

        $this->assertDatabaseHas('appointments', [
            'id'             => $appointment->id,
            'status'         => 'confirmed',
            'payment_status' => 'approved',
        ]);
    }

    /** @test */
    public function already_confirmed_appointment_is_not_double_processed(): void
    {
        Notification::fake();

        $owner      = User::factory()->barber()->create();
        $barbershop = Barbershop::factory()->active()->create(['user_id' => $owner->id]);
        $barber     = Barber::factory()->create(['barbershop_id' => $barbershop->id]);
        $service    = Service::factory()->create(['barbershop_id' => $barbershop->id]);
        $client     = User::factory()->create();

        $appointment = Appointment::factory()->create([
            'user_id'        => $client->id,
            'barber_id'      => $barber->id,
            'service_id'     => $service->id,
            'barbershop_id'  => $barbershop->id,
            'payment_id'     => 'mp_pay_002',
            'status'         => 'confirmed', // Already confirmed
            'payment_status' => 'approved',
        ]);

        $fakePayment          = new \stdClass();
        $fakePayment->id      = 'mp_pay_002';
        $fakePayment->status  = 'approved';

        $this->mock(PaymentClient::class, function ($mock) use ($fakePayment) {
            $mock->shouldReceive('get')->once()->andReturn($fakePayment);
        });

        $payload = $this->makePaymentPayload('mp_pay_002');
        $body    = json_encode($payload);

        $this->withHeaders(array_merge(
            $this->signedHeaders($body),
            ['Content-Type' => 'application/json']
        ))->post('/api/webhooks/mercadopago', $payload)
            ->assertStatus(200);

        // Notification should NOT be sent again
        Notification::assertNothingSent();
    }

    // -------------------------------------------------------------------------
    // Subscription renewal
    // -------------------------------------------------------------------------

    /** @test */
    public function subscription_preapproval_webhook_renews_subscription(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['cuts_per_month' => 4]);
        $sub  = Subscription::factory()->create([
            'user_id'         => $user->id,
            'plan_id'         => $plan->id,
            'external_id'     => 'mp_sub_abc',
            'uses_this_month' => 3,
            'status'          => 'active',
        ]);

        $payload = [
            'type'   => 'subscription_preapproval',
            'action' => 'subscription_preapproval.updated',
            'data'   => ['id' => 'mp_sub_abc'],
        ];
        $body = json_encode($payload);

        $this->withHeaders(array_merge(
            $this->signedHeaders($body),
            ['Content-Type' => 'application/json']
        ))->post('/api/webhooks/mercadopago', $payload)
            ->assertStatus(200)
            ->assertJsonPath('status', 'subscription_updated');

        $this->assertDatabaseHas('subscriptions', [
            'id'              => $sub->id,
            'status'          => 'active',
            'uses_this_month' => 0, // Counter reset
        ]);
    }
}
