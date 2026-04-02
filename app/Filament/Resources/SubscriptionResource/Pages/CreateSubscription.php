<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSubscription extends CreateRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        SubscriptionResource::assertUserBelongsToCurrentTenant($data['user_id'] ?? null);
        SubscriptionResource::assertPlanBelongsToCurrentTenant($data['plan_id'] ?? null);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Ao criar a assinatura manualmente no Filament,
        // vincula o cliente à barbearia caso ainda não esteja vinculado.
        $tenant = filament()->getTenant();
        $userId = $this->record->user_id;

        if ($tenant && $userId) {
            \App\Models\User::query()
                ->whereKey($userId)
                ->whereNull('barbershop_id')
                ->update(['barbershop_id' => $tenant->id]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return SubscriptionResource::getUrl('index', [
            'tenant' => filament()->getTenant()?->slug,
        ]);
    }
}
