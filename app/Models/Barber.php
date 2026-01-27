<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    // ADICIONE ESTE MÉTODO:
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}