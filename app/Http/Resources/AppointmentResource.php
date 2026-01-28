<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'price' => (float) $this->total_price,
            'scheduled_at' => $this->scheduled_at, // O Laravel serializa ISO-8601 por padrão, ótimo pro App
            'formatted_date' => Carbon::parse($this->scheduled_at)->format('d/m/Y H:i'), // Auxiliar visual
            
            // Relacionamentos limpos
            'barber' => [
                'name' => $this->barber->name,
                'avatar' => $this->barber->avatar_url ? url('storage/'.$this->barber->avatar_url) : null,
            ],
            'service' => [
                'name' => $this->service->name,
                'duration' => $this->service->duration_minutes . ' min',
            ],
            'barbershop' => [
                'name' => $this->barber->barbershop->name ?? 'Barbearia', // Útil para histórico global
            ]
        ];
    }
}