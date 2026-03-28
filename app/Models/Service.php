<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope('filament_tenant_service', function ($query) {
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
        'price',
        'duration_minutes',
        'description',
        'is_active',
    ];

    protected $casts = [
        'price'            => 'decimal:2',
        'duration_minutes' => 'integer',
        'is_active'        => 'boolean',
    ];

    // Relação: Um serviço pertence a uma Barbearia
    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class);
    }
}