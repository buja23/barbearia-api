<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barber extends Model
{
   use HasFactory;
   protected $fillable = [
    'barbershop_id',
    'name',
    'email',
    'phone',
    'avatar',
    'lunch_start',
    'lunch_end',
    'is_active',
    'commission_percentage',
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