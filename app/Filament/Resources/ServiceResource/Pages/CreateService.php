<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['barbershop_id'] = Filament::getTenant()?->id;

        return $data;
    }
}
