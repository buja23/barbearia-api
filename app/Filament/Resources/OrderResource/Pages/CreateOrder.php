<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenantId = Filament::getTenant()?->id;

        if (! $tenantId) {
            Notification::make()
                ->title('Barbearia não encontrada')
                ->body('Abra a venda dentro de uma barbearia ativa.')
                ->danger()
                ->send();

            $this->halt();
        }

        $items = $data['items'] ?? [];

        if (empty($items)) {
            Notification::make()
                ->title('Adicione ao menos um item')
                ->danger()
                ->send();

            $this->halt();
        }

        foreach ($items as $item) {
            $product = Product::query()
                ->where('barbershop_id', $tenantId)
                ->find($item['product_id'] ?? null);

            if (! $product) {
                Notification::make()
                    ->title('Produto inválido para esta barbearia')
                    ->danger()
                    ->send();

                $this->halt();
            }
        }

        $data['barbershop_id'] = $tenantId;
        $data['total_amount'] = Order::calculateTotalFromItems($items);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->refresh()->load('items.product');

        if ($this->record->status === 'approved') {
            $this->record->applyInventory();
        }
    }
}
