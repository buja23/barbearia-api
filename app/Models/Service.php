<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    /**
     * Os atributos que podem ser preenchidos em massa.
     * Ajuste conforme as colunas que você criou na sua migration.
     */
    protected $fillable = [
        'barbershop_id', // Chave estrangeira essencial para o relacionamento
        'name',
        'description',
        'price',
        'duration', // Em minutos, por exemplo
        'is_active',
    ];

    /**
     * Conversão de tipos automática.
     */
    protected $casts = [
        'price' => 'decimal:2',
        'duration' => 'integer',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relacionamentos
    |--------------------------------------------------------------------------
    */


    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class);
    }

    /**
     * Exemplo de relacionamento inverso (opcional):
     * Se você tiver agendamentos (Appointments) ligados a este serviço.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}