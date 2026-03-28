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

    protected function getRedirectUrl(): string
    {
        return SubscriptionResource::getUrl('index', [
            'tenant' => filament()->getTenant()?->slug,
        ]);
    }
}
