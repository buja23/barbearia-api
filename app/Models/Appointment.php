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
}
