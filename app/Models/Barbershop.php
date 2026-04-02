<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo as BelongsToAlias;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barbershop extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'phone',
        'address',
        'pix_key',
        'pix_key_type',
        'logo_path',
        'subscription_status',
        'trial_ends_at',
        'subscription_expires_at',
        'subscription_plan',
        'saas_plan_id',
        'saas_payment_id',
        'saas_last_payment_id',
        'saas_pix_copy_paste',
        'saas_pix_qr_code',
        'mp_access_token',
        'mp_public_key',
        'mp_oauth_state',
    ];

    /**
     * Campos que NUNCA devem aparecer em toJson() / toArray() / API responses.
     * Proteção contra vazamento acidental de dados sensíveis de pagamento.
     */
    protected $hidden = [
        'saas_pix_copy_paste',
        'saas_pix_qr_code',
        'saas_payment_id',
        'saas_last_payment_id',
        'pix_key',
        'mp_access_token',  // nunca expor o token secreto na API
    ];

    protected $casts = [
        'trial_ends_at'           => 'datetime',
        'subscription_expires_at' => 'datetime',
    ];

    // --- Relacionamentos ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function saasPlan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SaasPlan::class, 'saas_plan_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    public function barbers(): HasMany
    {
        return $this->hasMany(Barber::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function openingHours(): HasMany
    {
        return $this->hasMany(OpeningHour::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // --- Lógica de Assinatura SaaS ---

    /**
     * Verifica se a barbearia tem acesso ativo (trial ou assinatura paga).
     */
    public function hasActiveAccess(): bool
    {
        return $this->isOnTrial() || $this->isSubscriptionActive();
    }

    /**
     * Verifica se ainda está no período de trial.
     */
    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trial'
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isFuture();
    }

    /**
     * Verifica se a assinatura paga está ativa e não expirou.
     */
    public function isSubscriptionActive(): bool
    {
        return in_array($this->subscription_status, ['active', 'cancelled'])
            && $this->subscription_expires_at !== null
            && $this->subscription_expires_at->isFuture();
    }

    /**
     * Verifica se o acesso está bloqueado (trial/assinatura expirados).
     */
    public function isAccessBlocked(): bool
    {
        return !$this->hasActiveAccess();
    }

    /**
     * Dias restantes de trial (retorna 0 se expirado).
     */
    public function trialDaysRemaining(): int
    {
        if (!$this->isOnTrial()) {
            return 0;
        }
        return (int) now()->diffInDays($this->trial_ends_at, false);
    }
}