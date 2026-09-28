<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Barbershop;
use App\Models\OpeningHour;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use App\Support\DemoAccess;
use Illuminate\Database\Eloquent\Model;

class DemoProtectionObserver
{
    private function enabled(): bool
    {
        // Maintenance seeders remain usable; HTTP tests must exercise the guards.
        return DemoAccess::enabled()
            && (! app()->runningInConsole() || app()->runningUnitTests());
    }

    public function saving(Model $record): void
    {
        if (! $this->enabled()) {
            return;
        }

        if ($record instanceof User) {
            if ($record->exists && DemoAccess::isDemoUser($record)
                && $record->isDirty(['email', 'password', 'role', 'barbershop_id'])) {
                DemoAccess::deny();
            }

            return;
        }

        if (($record instanceof Barbershop || $record instanceof OpeningHour)
            && DemoAccess::protects($record)) {
            DemoAccess::deny();
        }

        if (! DemoAccess::isDemoUser()) {
            return;
        }

        $tenantId = DemoAccess::tenantQuery()->value('id');
        $recordTenantId = $record instanceof OrderItem
            ? $record->order()->value('barbershop_id') : $record->barbershop_id;

        if (! $tenantId || (string) $recordTenantId !== (string) $tenantId
            || ($record->exists && $record->isDirty('barbershop_id'))
            || ($record instanceof OrderItem && $record->exists && $record->isDirty('order_id'))) {
            DemoAccess::deny();
        }

        $relations = match (true) {
            $record instanceof Appointment => ['barber_id' => Barber::class, 'service_id' => Service::class, 'user_id' => User::class],
            $record instanceof Subscription => ['plan_id' => Plan::class, 'user_id' => User::class],
            $record instanceof OrderItem => ['product_id' => Product::class],
            default => [],
        };

        foreach ($relations as $field => $model) {
            if ($record->{$field} && ! $model::query()->whereKey($record->{$field})
                ->where('barbershop_id', $tenantId)->exists()) {
                DemoAccess::deny();
            }
        }

        // Financial and destructive state transitions are not a demo sandbox.
        if (($record instanceof Order && $record->isDirty(['status', 'inventory_applied_at'])
                && ($record->exists || $record->status !== 'pending'))
            || ($record instanceof Appointment && $record->exists
                && $record->isDirty(['status', 'payment_status', 'payment_method']))
            || ($record instanceof Appointment && ! $record->exists
                && (in_array($record->status, ['completed', 'canceled', 'no_show'], true)
                    || $record->payment_status === 'approved'))
            || $record instanceof Subscription) {
            DemoAccess::deny();
        }
    }

    public function deleting(Model $record): void
    {
        if ($this->enabled()) {
            DemoAccess::ensureAllowed($record);
        }
    }
}
