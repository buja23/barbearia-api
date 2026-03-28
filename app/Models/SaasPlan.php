<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaasPlan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'billing_cycle_months',
        'features',
        'is_active',
        'is_popular',
        'sort_order',
    ];

    protected $casts = [
        'price'      => 'decimal:2',
        'billing_cycle_months' => 'integer',
        'features'   => 'array',
        'is_active'  => 'boolean',
        'is_popular' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function barbershops(): HasMany
    {
        return $this->hasMany(Barbershop::class, 'saas_plan_id');
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'R$ ' . number_format((float) $this->price, 2, ',', '.');
    }

    public function getBillingCycleLabelAttribute(): string
    {
        return match ($this->billing_cycle_months) {
            1 => 'mensal',
            6 => 'semestral',
            12 => 'anual',
            default => 'a cada ' . $this->billing_cycle_months . ' meses',
        };
    }

    public function getBillingCycleDescriptionAttribute(): string
    {
        return match ($this->billing_cycle_months) {
            1 => 'cobrança mês a mês',
            6 => 'pagamento único para 6 meses',
            12 => 'pagamento único para 12 meses',
            default => 'pagamento para ' . $this->billing_cycle_months . ' meses',
        };
    }

    public function getMonthlyEquivalentAttribute(): float
    {
        $months = max(1, (int) $this->billing_cycle_months);

        return round((float) $this->price / $months, 2);
    }
}
