<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'total_amount', 'status', 'payment_id', 
        'pix_copy_paste', 'qr_code_base64'
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}