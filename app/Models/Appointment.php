<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = [
        'barber_id',
        'service_id',
        'user_id',
        'client_name',
        'client_phone',
        'scheduled_at',
        'end_at',
        'total_price',
        'status',
        'notes',
        'payment_id',
        'payment_status',
        'payment_method',
        'pix_copy_paste',
        'pix_qr_code_url',
        'reminder_sent',
        'barber_commission_value',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'end_at'       => 'datetime',
        'total_price'  => 'decimal:2',
    ];

    // --- Relacionamentos ---

    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
{
    static::updating(function ($appointment) {
        // Quando o status mudar para 'completed', calculamos a comissão
        if ($appointment->isDirty('status') && in_array($appointment->status, ['completed'])) {
                $barber = $appointment->barber;
                if ($barber && $barber->commission_percentage) {
                    $appointment->barber_commission_value = ($appointment->total_price * $barber->commission_percentage) / 100;
                }
            
            // Se for assinante, incrementa o uso do mês
            if ($appointment->user && $appointment->user->subscription) {
                $appointment->user->subscription->increment('uses_this_month');
            }
        }
    });
}
}
