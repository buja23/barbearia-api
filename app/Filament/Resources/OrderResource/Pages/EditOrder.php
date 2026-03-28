<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $tenantId = Filament::getTenant()?->id;
        $items = $data['items'] ?? [];

        if (! $tenantId || empty($items)) {
            Notification::make()
                ->title('Venda inválida')
                ->body('A venda precisa ter uma barbearia ativa e ao menos um item.')
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

        if ($this->record->inventory_applied_at && $this->itemsWereChanged($items)) {
            Notification::make()
                ->title('Itens bloqueados após aprovação')
                ->body('Cancele a venda primeiro para alterar os itens.')
                ->danger()
                ->send();

            $this->halt();
        }

        $data['barbershop_id'] = $tenantId;
        $data['total_amount'] = Order::calculateTotalFromItems($items);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->refresh()->load('items.product');

        if ($this->record->status === 'approved') {
            $this->record->applyInventory();

            return;
        }

        $this->record->releaseInventory();
    }

    protected function itemsWereChanged(array $incomingItems): bool
    {
        $currentItems = $this->record->items()
            ->orderBy('id')
            ->get(['product_id', 'quantity', 'unit_price', 'cost_price'])
            ->map(fn ($item) => [
                'product_id' => (int) $item->product_id,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'cost_price' => (float) $item->cost_price,
            ])
            ->values()
            ->all();

        $normalizedIncoming = collect($incomingItems)
            ->map(fn (array $item) => [
                'product_id' => (int) ($item['product_id'] ?? 0),
                'quantity' => (int) ($item['quantity'] ?? 0),
                'unit_price' => (float) ($item['unit_price'] ?? 0),
                'cost_price' => (float) ($item['cost_price'] ?? 0),
            ])
            ->values()
            ->all();

        return $currentItems !== $normalizedIncoming;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
