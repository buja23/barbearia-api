<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model {
    use HasFactory;
    protected $fillable = ['barbershop_id', 'name', 'description', 'price', 'cuts_per_month', 'is_active'];

    protected $casts = [
        'price'          => 'decimal:2',
        'cuts_per_month' => 'integer',
        'is_active'      => 'boolean',
    ];

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}