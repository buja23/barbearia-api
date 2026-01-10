<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Barber extends Model
{
   protected $fillable = [
    'barbershop_id',
    'name',
    'email',
    'phone',
    'avatar',
    'lunch_start', 
    'lunch_end',   
    'is_active',
];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relacionamento com a Barbearia
    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class);
    }
}