<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'barbershop_id',
        'name',
        'price',
        'duration_minutes',
        'description',
        'is_active',
    ];

    // Relação: Um serviço pertence a uma Barbearia
    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class);
    }
}