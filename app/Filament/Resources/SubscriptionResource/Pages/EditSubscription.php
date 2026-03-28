<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubscription extends EditRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        SubscriptionResource::assertUserBelongsToCurrentTenant($data['user_id'] ?? null);
        SubscriptionResource::assertPlanBelongsToCurrentTenant($data['plan_id'] ?? null);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
