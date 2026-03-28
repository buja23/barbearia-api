<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barber extends Model
{
   use HasFactory;

   protected static function booted(): void
   {
       static::addGlobalScope('filament_tenant_barber', function ($query) {
           if (! app()->runningInConsole()) {
               $tenantId = Filament::getTenant()?->id;

               if ($tenantId) {
                   $query->where('barbershop_id', $tenantId);
               }
           }
       });
   }

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