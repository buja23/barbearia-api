<?php

namespace App\Support;

use App\Models\Barbershop;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class DemoAccess
{
    public const MESSAGE = 'Esta ação está desativada no ambiente demonstrativo.';

    public static function enabled(): bool
    {
        return (bool) config('demo.enabled');
    }

    public static function isDemoUser(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! self::enabled() || ! $user) {
            return false;
        }

        if (filled($id = config('demo.user_id'))) {
            return (string) $user->getKey() === (string) $id;
        }

        // Use persisted identity, even while a submitted email is being changed.
        return filled(config('demo.email')) && strcasecmp((string) ($user->getRawOriginal('email') ?? $user->email),
            (string) config('demo.email')) === 0;
    }

    public static function tenantQuery(): Builder
    {
        return Barbershop::query()->where(
            filled(config('demo.tenant_id')) ? 'id' : 'slug',
            filled(config('demo.tenant_id')) ? config('demo.tenant_id') : config('demo.tenant_slug'),
        )->when(blank(config('demo.tenant_id')) && blank(config('demo.tenant_slug')),
            fn (Builder $query) => $query->whereRaw('1 = 0'));
    }

    public static function isDemoTenant(?Barbershop $tenant): bool
    {
        if (! self::enabled() || ! $tenant) {
            return false;
        }

        if (filled($id = config('demo.tenant_id'))) {
            return (string) $tenant->getKey() === (string) $id;
        }

        return filled(config('demo.tenant_slug'))
            && ($tenant->getRawOriginal('slug') ?? $tenant->slug) === config('demo.tenant_slug');
    }

    public static function protects(?Model $record = null): bool
    {
        if (! self::enabled()) {
            return false;
        }

        if (self::isDemoUser()) {
            return true;
        }

        if ($record instanceof Barbershop) {
            return self::isDemoTenant($record);
        }

        if ($record instanceof User) {
            return self::isDemoUser($record);
        }

        if ($record instanceof OrderItem) {
            // Do not trust a loaded relationship after order_id has changed.
            return Order::query()->whereIn('id', array_filter([
                $record->order_id, $record->getRawOriginal('order_id'),
            ]))->whereIn('barbershop_id', self::tenantQuery()->select('id'))->exists();
        }

        return $record && self::tenantQuery()->whereKey(array_filter([
            $record->barbershop_id, $record->getRawOriginal('barbershop_id'),
        ]))->exists();
    }

    public static function ensureAllowed(?Model $record = null): void
    {
        if (self::protects($record)) {
            self::deny();
        }
    }

    public static function deny(): never
    {
        if (request()->hasSession() && ! request()->is('api/*')) {
            Notification::make()->title(self::MESSAGE)->warning()->send();
        }

        throw ValidationException::withMessages(['demo' => self::MESSAGE]);
    }
}
