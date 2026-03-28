<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'barbershop_id', 'total_amount', 'status', 'payment_id',
        'pix_copy_paste', 'qr_code_base64', 'inventory_applied_at'
    ];

    protected $casts = [
        'total_amount'          => 'decimal:2',
        'inventory_applied_at'  => 'datetime',
    ];

    public static function calculateTotalFromItems(array $items): float
    {
        return collect($items)->sum(function (array $item): float {
            $quantity = (int) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);

            return $quantity * $unitPrice;
        });
    }

    public function applyInventory(): void
    {
        if ($this->inventory_applied_at) {
            return;
        }

        $this->loadMissing('items.product');

        foreach ($this->items as $item) {
            $product = $item->product;

            if (! $product) {
                throw new RuntimeException('Produto da venda não encontrado.');
            }

            if ((int) $product->barbershop_id !== (int) $this->barbershop_id) {
                throw new RuntimeException('Produto não pertence à mesma barbearia da venda.');
            }

            if ((int) $product->quantity < (int) $item->quantity) {
                throw new RuntimeException("Estoque insuficiente para o produto {$product->name}.");
            }
        }

        foreach ($this->items as $item) {
            $item->product->decrement('quantity', (int) $item->quantity);
        }

        $this->forceFill(['inventory_applied_at' => now()])->saveQuietly();
    }

    public function releaseInventory(): void
    {
        if (! $this->inventory_applied_at) {
            return;
        }

        $this->loadMissing('items.product');

        foreach ($this->items as $item) {
            if ($item->product) {
                $item->product->increment('quantity', (int) $item->quantity);
            }
        }

        $this->forceFill(['inventory_applied_at' => null])->saveQuietly();
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}