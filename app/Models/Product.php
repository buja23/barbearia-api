<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'cost_price',
        'sale_price',
        'quantity',
        'min_stock_alert',
        'type',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    // Helper para saber se está com estoque baixo
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->min_stock_alert;
    }
}