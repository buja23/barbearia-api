<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'name'   => $this->name,
            // Retorna URL completa para o App não ter que adivinhar o domínio
            'avatar' => $this->avatar_url ? url('storage/' . $this->avatar_url) : null,
            'bio'    => $this->bio,
            'role'   => 'Barbeiro Profissional', // Exemplo de campo extra
        ];
    }
}