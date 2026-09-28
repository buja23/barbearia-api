<?php

namespace Tests\Feature;

use App\Filament\Pages\BillingPage;
use App\Filament\Pages\Tenancy\RegisterBarbershop;
use App\Filament\Resources;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Http\Middleware\ProtectDemoApi;
use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Barbershop;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use App\Observers\DemoProtectionObserver;
use App\Services\PaymentService;
use App\Support\DemoAccess;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DemoProtectionTest extends TestCase
{
    private User $demo;
    private User $owner;
    private Barbershop $tenant;
    private Barbershop $otherTenant;

    protected function setUp(): void
    {
        parent::setUp();

        config(['demo.enabled' => false, 'demo.user_id' => null, 'demo.tenant_id' => null,
            'demo.email' => 'demo@barbearia.app', 'demo.tenant_slug' => 'barbearia-demo']);

        $this->demo = User::factory()->barber()->create(['email' => config('demo.email')]);
        $this->tenant = Barbershop::factory()->active()->create([
            'user_id' => $this->demo->id, 'slug' => config('demo.tenant_slug'),
        ]);
        $this->demo->update(['barbershop_id' => $this->tenant->id]);
        $this->owner = User::factory()->barber()->create();
        $this->otherTenant = Barbershop::factory()->active()->create(['user_id' => $this->owner->id]);

        config(['demo.enabled' => true]);
        Http::preventStrayRequests();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::setTenant($this->tenant);
    }

    private function assertBlocked(callable $action): void
    {
        try {
            $action();
            $this->fail('The demo action was allowed.');
        } catch (ValidationException $exception) {
            $this->assertSame([DemoAccess::MESSAGE], $exception->errors()['demo']);
        }
    }

    public static function identityFields(): array
    {
        return [
            ['email', 'changed@example.com'], ['password', 'NewPassword123'],
            ['role', 'admin'], ['barbershop_id', null],
        ];
    }

    #[DataProvider('identityFields')]
    public function test_demo_identity_cannot_be_changed(string $field, mixed $value): void
    {
        $original = $this->demo->getRawOriginal($field);
        $this->actingAs($this->demo);
        $this->assertBlocked(fn () => $this->demo->forceFill([$field => $value])->save());
        $this->assertSame($original, $this->demo->fresh()->getRawOriginal($field));
    }

    public function test_ids_take_precedence_and_demo_cannot_access_another_owned_tenant(): void
    {
        config(['demo.enabled' => false]);
        $additional = Barbershop::factory()->create(['user_id' => $this->demo->id]);
        config(['demo.enabled' => true, 'demo.user_id' => $this->demo->id,
            'demo.tenant_id' => $this->tenant->id, 'demo.email' => 'unused@example.com',
            'demo.tenant_slug' => 'unused-slug']);

        $this->assertTrue(DemoAccess::isDemoUser($this->demo));
        $this->assertTrue($this->demo->canAccessTenant($this->tenant));
        $this->assertFalse($this->demo->canAccessTenant($additional));
        $this->assertSame([$this->tenant->id], $this->demo->getTenants(Filament::getPanel('admin'))->modelKeys());
    }

    public function test_api_is_read_only_for_demo_and_password_reset_is_blocked(): void
    {
        Sanctum::actingAs($this->demo);
        $this->getJson('/api/user')->assertOk();
        $this->putJson('/api/user', [
            'name' => 'Changed', 'email' => 'changed@example.com', 'password' => 'NewPassword123',
            'role' => 'admin', 'barbershop_id' => $this->otherTenant->id,
        ])->assertForbidden()->assertJsonPath('message', DemoAccess::MESSAGE);
        foreach (['/api/user', '/api/subscribe', '/api/subscribe/cancel', '/api/appointments'] as $url) {
            $this->json($url === '/api/user' ? 'PUT' : 'POST', $url, [])
                ->assertForbidden()->assertJsonPath('message', DemoAccess::MESSAGE);
        }
        $this->deleteJson('/api/appointments/1')->assertForbidden();
        $this->postJson('/api/logout')->assertOk();
        $this->postJson('/api/password/reset', ['email' => $this->demo->email])
            ->assertForbidden()->assertJsonPath('message', DemoAccess::MESSAGE);
    }

    public function test_api_rejects_other_tenant_and_cross_tenant_slot_ids(): void
    {
        $barber = Barber::factory()->create(['barbershop_id' => $this->otherTenant->id]);
        Sanctum::actingAs($this->demo);

        $this->getJson('/api/'.$this->tenant->slug)->assertOk();
        $this->getJson('/api/'.$this->otherTenant->slug)->assertForbidden();
        $this->getJson('/api/'.$this->tenant->slug.'/slots?barber_id='.$barber->id)->assertForbidden();
    }

    public function test_password_recovery_is_blocked_without_authentication(): void
    {
        foreach (['forgot', 'reset'] as $action) {
            $this->postJson('/api/password/'.$action, ['email' => $this->demo->email])
                ->assertForbidden()->assertJsonPath('message', DemoAccess::MESSAGE);
        }
    }

    public function test_demo_never_gains_global_admin_access_from_a_misconfigured_role(): void
    {
        config(['demo.enabled' => false]);
        $this->demo->update(['role' => 'admin']);
        config(['demo.enabled' => true]);
        $this->actingAs($this->demo);

        $this->assertFalse($this->demo->isAdmin());
        $this->assertFalse(Resources\ReportResource::canAccess());
        $this->assertFalse($this->demo->canAccessTenant($this->otherTenant));
    }

    public function test_demo_cannot_create_or_reconfigure_a_barbershop(): void
    {
        $this->actingAs($this->demo);
        $this->assertFalse(RegisterBarbershop::canView());
        $this->assertFalse(Resources\BarbershopResource::canCreate());
        $this->assertFalse(Resources\BarbershopResource::canEdit($this->tenant));
        $this->assertBlocked(fn () => Barbershop::create(['user_id' => $this->demo->id, 'name' => 'Extra', 'slug' => 'extra']));

        foreach (['name' => 'Changed', 'slug' => 'changed', 'pix_key' => 'changed@example.com',
            'mp_access_token' => 'blocked-input', 'subscription_status' => 'cancelled'] as $field => $value) {
            $this->assertBlocked(fn () => $this->tenant->fresh()->update([$field => $value]));
        }
    }

    public function test_all_eight_resources_deny_single_and_bulk_deletion(): void
    {
        $this->actingAs($this->demo);
        foreach ([
            Resources\BarbershopResource::class => $this->tenant,
            Resources\BarberResource::class => new Barber(),
            Resources\ServiceResource::class => new Service(),
            Resources\ProductResource::class => new Product(),
            Resources\AppointmentResource::class => new Appointment(),
            Resources\OrderResource::class => new Order(),
            Resources\PlanResource::class => new Plan(),
            Resources\SubscriptionResource::class => new Subscription(),
        ] as $resource => $record) {
            $this->assertFalse($resource::canDelete($record), $resource);
            $this->assertFalse($resource::canDeleteAny(), $resource);
            $this->assertFalse($resource::canForceDelete($record), $resource);
            $this->assertFalse($resource::canForceDeleteAny(), $resource);
        }

        $this->assertBlocked(fn () => $this->tenant->delete());
        $this->assertDatabaseHas('barbershops', ['id' => $this->tenant->id]);
    }

    public function test_payment_service_can_be_resolved_without_credentials_and_demo_cannot_charge(): void
    {
        config(['services.mercadopago.token' => null]);
        $service = app(PaymentService::class);
        $this->actingAs($this->demo);

        foreach ([
            fn () => $service->createPixPayment(new Appointment()),
            fn () => $service->generateLocalPixPayment(new Appointment()),
            fn () => $service->createOrderPix(new Order()),
            fn () => $service->createSubscriptionPix(new Subscription()),
            fn () => $service->createSubscriptionCard(new Subscription(), 'unused'),
            fn () => $service->createSaasPix($this->tenant, new \App\Models\SaasPlan()),
            fn () => $service->createSaasCardCheckout($this->tenant, new \App\Models\SaasPlan()),
            fn () => $service->createSaasCardPayment($this->tenant, new \App\Models\SaasPlan(), []),
        ] as $operation) {
            $this->assertBlocked($operation);
        }
    }

    public function test_demo_tenant_payments_are_blocked_even_without_demo_actor(): void
    {
        $this->actingAs($this->owner);
        $this->assertBlocked(fn () => app(PaymentService::class)->createSaasPix($this->tenant, new \App\Models\SaasPlan()));
        $this->assertBlocked(fn () => $this->tenant->update(['pix_key' => 'changed@example.com']));
    }

    public function test_billing_methods_cannot_be_called_directly_by_demo(): void
    {
        $this->actingAs($this->demo);
        $page = new BillingPage();
        foreach ([
            fn () => $page->selectPlan(1), fn () => $page->checkoutWithCard(1),
            fn () => $page->initiateCardPayment(1), fn () => $page->processCardPayment(1, []),
            fn () => $page->cancelSubscription(),
        ] as $operation) {
            $this->assertBlocked($operation);
        }
    }

    public function test_oauth_is_blocked_before_http_calls(): void
    {
        config(['demo.enabled' => false]);
        $this->tenant->update(['mp_oauth_state' => 'existing-demo-state']);
        config(['demo.enabled' => true]);
        $this->actingAs($this->demo);

        $this->get('/mp/connect')->assertForbidden();
        $this->get('/mp/disconnect')->assertForbidden();
        $this->get('/mp/callback?code=unused&state=existing-demo-state')->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_real_accounts_keep_profile_and_tenant_mutations(): void
    {
        $this->actingAs($this->owner);
        Filament::setTenant($this->otherTenant);
        $this->otherTenant->update(['name' => 'Updated real shop']);
        $this->assertTrue(Resources\BarbershopResource::canCreate());
        $this->assertTrue(Resources\BarbershopResource::canDelete($this->otherTenant));
        $this->assertTrue($this->owner->canAccessTenant($this->otherTenant));
        $this->assertFalse($this->owner->canAccessTenant($this->tenant));

        Sanctum::actingAs($this->owner);
        $this->putJson('/api/user', ['name' => 'Updated owner', 'email' => 'owner@example.com'])->assertOk();
        $this->assertDatabaseHas('barbershops', ['id' => $this->otherTenant->id, 'name' => 'Updated real shop']);
    }

    public function test_verified_demo_webhook_is_blocked_before_payment_lookup(): void
    {
        config(['demo.enabled' => false]);
        $subscription = Subscription::factory()->create([
            'barbershop_id' => $this->tenant->id, 'external_id' => '12345', 'status' => 'pending',
        ]);
        config(['demo.enabled' => true, 'services.mercadopago.token' => null]);
        $this->mock(PaymentService::class)->shouldNotReceive('getPayment');
        $timestamp = (string) time();
        $requestId = 'demo-webhook-test';
        $manifest = "id:12345;request-id:{$requestId};ts:{$timestamp};";
        $signature = hash_hmac('sha256', $manifest, config('services.mercadopago.webhook_secret'));

        $this->postJson('/api/webhooks/mercadopago', [
            'type' => 'payment', 'data' => ['id' => '12345'],
        ], ['x-signature' => "ts={$timestamp},v1={$signature}", 'x-request-id' => $requestId])
            ->assertForbidden()->assertJsonPath('message', DemoAccess::MESSAGE);

        $this->assertSame('pending', $subscription->fresh()->status);
    }

    public function test_disabled_demo_mode_preserves_existing_behavior(): void
    {
        config(['demo.enabled' => false]);
        $this->actingAs($this->demo);
        $this->demo->update(['email' => 'new@example.com']);
        $this->tenant->update(['name' => 'Updated shop']);
        $this->assertFalse(DemoAccess::isDemoUser($this->demo));
        $this->assertFalse(DemoAccess::isDemoTenant($this->tenant));
        $this->assertFalse(DemoAccess::protects($this->tenant));
        $this->assertTrue(Resources\BarbershopResource::canDelete($this->tenant));
    }

    public function test_fallback_identity_and_non_matching_ids_do_not_classify_real_accounts(): void
    {
        $this->assertTrue(DemoAccess::isDemoUser($this->demo));
        $this->assertTrue(DemoAccess::isDemoTenant($this->tenant));
        $this->assertFalse(DemoAccess::isDemoUser($this->owner));
        $this->assertFalse(DemoAccess::isDemoTenant($this->otherTenant));

        // A configured ID is authoritative; never fall back after an ID mismatch.
        config(['demo.user_id' => -1, 'demo.tenant_id' => -1]);
        $this->assertFalse(DemoAccess::isDemoUser($this->demo));
        $this->assertFalse(DemoAccess::isDemoTenant($this->tenant));
        $this->assertFalse(DemoAccess::tenantQuery()->exists());

        config(['demo.user_id' => null, 'demo.tenant_id' => null,
            'demo.email' => '', 'demo.tenant_slug' => '']);
        $this->assertFalse(DemoAccess::isDemoUser(new User()));
        $this->assertFalse(DemoAccess::isDemoTenant(new Barbershop()));
        $this->assertFalse(DemoAccess::tenantQuery()->exists());
    }

    public function test_original_demo_identity_is_protected_without_demo_actor(): void
    {
        $this->actingAs($this->owner);
        $this->assertBlocked(fn () => $this->demo->update(['email' => 'renamed@example.com']));
        $this->assertBlocked(fn () => $this->tenant->update(['slug' => 'renamed-demo']));
    }

    public function test_demo_can_login_and_logout_with_a_real_sanctum_token(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => $this->demo->email, 'password' => 'password',
        ])->assertOk();
        $token = $response->json('access_token');
        $this->assertNotEmpty($token);
        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->assertSame(0, $this->demo->tokens()->count());
    }

    public function test_middleware_blocks_every_write_verb_including_patch(): void
    {
        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $request = Request::create('/api/user', $method);
            $request->setUserResolver(fn ($guard = null) => $this->demo);
            $response = app(ProtectDemoApi::class)->handle($request, function () {
                $this->fail('Demo write reached the controller.');
            });
            $this->assertSame(403, $response->getStatusCode());
            $this->assertSame(DemoAccess::MESSAGE, json_decode($response->getContent(), true)['message']);
        }
    }

    public function test_public_reads_and_real_admin_remain_available(): void
    {
        $this->getJson('/api/'.$this->otherTenant->slug)->assertOk();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        Filament::setTenant($this->otherTenant);
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->canAccessTenant($this->otherTenant));
        $this->assertTrue(Resources\ReportResource::canAccess());
        $this->assertTrue(Resources\BarbershopResource::canCreate());
        $this->assertTrue(Resources\BarbershopResource::canEdit($this->otherTenant));
        $this->assertTrue(Resources\BarbershopResource::canDelete($this->otherTenant));
        $service = Service::factory()->create(['barbershop_id' => $this->otherTenant->id]);
        $service->update(['name' => 'Real service']);
        $service->delete();
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_order_items_do_not_trust_cached_relations_or_allow_orphan_writes(): void
    {
        config(['demo.enabled' => false]);
        $demoOrder = Order::create(['barbershop_id' => $this->tenant->id, 'status' => 'pending', 'total_amount' => 10]);
        $realOrder = Order::create(['barbershop_id' => $this->otherTenant->id, 'status' => 'pending', 'total_amount' => 10]);
        config(['demo.enabled' => true]);
        $observer = app(DemoProtectionObserver::class);

        $this->actingAs($this->owner);
        $item = new OrderItem(['order_id' => $demoOrder->id]);
        $item->setRelation('order', $realOrder);
        $this->assertBlocked(fn () => $observer->deleting($item));
        $this->assertFalse(DemoAccess::protects(new OrderItem(['order_id' => $realOrder->id])));
        $this->assertFalse(DemoAccess::protects(new OrderItem()));

        $this->actingAs($this->demo);
        $item = new OrderItem(['order_id' => $realOrder->id]);
        $item->setRelation('order', $demoOrder);
        $this->assertBlocked(fn () => $observer->saving($item));
        $this->assertBlocked(fn () => $observer->saving(new OrderItem()));
    }

    public function test_edit_order_is_blocked_before_relationships_or_inventory_are_saved(): void
    {
        $this->actingAs($this->demo);
        $record = new Order(['barbershop_id' => $this->tenant->id, 'status' => 'approved']);
        $this->assertFalse(Resources\OrderResource::canEdit($record));
        $page = new EditOrder();
        $page->record = $record;
        $hook = new \ReflectionMethod(EditOrder::class, 'beforeValidate');
        $this->assertBlocked(fn () => $hook->invoke($page));

        $this->actingAs($this->owner);
        $realRecord = new Order(['barbershop_id' => $this->otherTenant->id]);
        $this->assertTrue(Resources\OrderResource::canEdit($realRecord));
    }

    public function test_appointment_form_options_do_not_expose_other_tenant_ids_to_demo(): void
    {
        $realService = Service::factory()->create(['barbershop_id' => $this->otherTenant->id]);
        $demoService = Service::factory()->create(['barbershop_id' => $this->tenant->id]);
        $scope = new \ReflectionMethod(Resources\AppointmentResource::class, 'scopeDemoOptions');
        $this->actingAs($this->demo);
        $this->assertSame([$demoService->id], $scope->invoke(null, Service::query())->pluck('id')->all());

        $this->actingAs($this->owner);
        $this->assertTrue($scope->invoke(null, Service::query())->whereKey($realService->id)->exists());
    }
}
