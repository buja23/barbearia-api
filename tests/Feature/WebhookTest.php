<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Barbershop;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Generates x-signature and x-request-id headers that pass the controller's
     * validateMercadoPagoSignature() check.
     * Format: ts=<ts>,v1=hmac_sha256("id:<dataId>;request-id:<reqId>;ts:<ts>;", secret)
     */
    private function signedHeaders(string $dataId = '', string $secret = 'test-webhook-secret'): array
    {
        $ts        = (string) now()->timestamp;
        $requestId = $ts . '_' . random_int(1000, 9999);
        $manifest  = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $hash      = hash_hmac('sha256', $manifest, $secret);

        return [
            'x-signature'  => "ts={$ts},v1={$hash}",
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

    #[Test]
    public function webhook_with_invalid_signature_is_rejected(): void
    {
        $this->withHeaders([
            'x-signature'  => 'ts=12345,v1=invalid-hash',
            'x-request-id' => (string) now()->timestamp,
            'Content-Type' => 'application/json',
        ])->postJson('/api/webhooks/mercadopago', ['type' => 'payment', 'data' => ['id' => '123']])
            ->assertStatus(403);
    }

    #[Test]
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

    #[Test]
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

        $fakePayment           = new \stdClass();
        $fakePayment->id       = 'mp_pay_001';
        $fakePayment->status   = 'approved';
        $fakePayment->external_reference = null;

        $this->mock(PaymentService::class, function ($mock) use ($fakePayment) {
            $mock->shouldReceive('getPayment')->once()->andReturn($fakePayment);
        });

        $payload = $this->makePaymentPayload('mp_pay_001');

        $this->withHeaders(array_merge(
            $this->signedHeaders('mp_pay_001'),
            ['Content-Type' => 'application/json']
        ))->postJson('/api/webhooks/mercadopago', $payload)
            ->assertStatus(200)
            ->assertJsonPath('status', 'payment_updated');

        $this->assertDatabaseHas('appointments', [
            'id'             => $appointment->id,
            'status'         => 'confirmed',
            'payment_status' => 'approved',
        ]);
    }

    #[Test]
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
            'status'         => 'confirmed',
            'payment_status' => 'approved',
        ]);

        $fakePayment                     = new \stdClass();
        $fakePayment->id                 = 'mp_pay_002';
        $fakePayment->status             = 'approved';
        $fakePayment->external_reference = null;

        $this->mock(PaymentService::class, function ($mock) use ($fakePayment) {
            $mock->shouldReceive('getPayment')->once()->andReturn($fakePayment);
        });

        $payload = $this->makePaymentPayload('mp_pay_002');

        $this->withHeaders(array_merge(
            $this->signedHeaders('mp_pay_002'),
            ['Content-Type' => 'application/json']
        ))->postJson('/api/webhooks/mercadopago', $payload)
            ->assertStatus(200);

        Notification::assertNothingSent();
    }

    // -------------------------------------------------------------------------
    // Subscription renewal
    // -------------------------------------------------------------------------

    #[Test]
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

        $this->withHeaders(array_merge(
            $this->signedHeaders('mp_sub_abc'),
            ['Content-Type' => 'application/json']
        ))->postJson('/api/webhooks/mercadopago', $payload)
            ->assertStatus(200)
            ->assertJsonPath('status', 'subscription_updated');

        $this->assertDatabaseHas('subscriptions', [
            'id'              => $sub->id,
            'status'          => 'active',
            'uses_this_month' => 0,
        ]);
    }
}

