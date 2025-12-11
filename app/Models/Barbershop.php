<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // <--- Importante

class Barbershop extends Model
{
    use HasFactory;

    // Libera esses campos para serem salvos pelo Filament
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'phone',
        'address',
        'logo_path',
    ];

    // Essa é a função que o Filament estava procurando e não achou!
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}