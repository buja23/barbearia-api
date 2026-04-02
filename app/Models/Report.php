<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'barbershop_id',
        'type',
        'title',
        'description',
        'image_path',
        'source',
        'status',
        'admin_notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class);
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'bug'        => '🐛 Bug',
            'suggestion' => '💡 Sugestão',
            'other'      => '💬 Outro',
            default      => $this->type,
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'open'        => 'Aberto',
            'in_progress' => 'Em análise',
            'resolved'    => 'Resolvido',
            default       => $this->status,
        };
    }
}
