<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Lógica para tratar avatar local vs externo
        // Ajuste 'avatar' ou 'avatar_url' conforme o nome real no seu banco
        $avatarPath = $this->barber->avatar ?? $this->barber->avatar_url; 
        
        $avatarFinal = null;
        if ($avatarPath) {
            $avatarFinal = Str::startsWith($avatarPath, 'http') 
                ? $avatarPath 
                : url('storage/' . $avatarPath);
        }

        return [
            'id' => $this->id,
            'status' => $this->status,
            'price' => (float) $this->total_price,
            'scheduled_at' => $this->scheduled_at,
            'formatted_date' => Carbon::parse($this->scheduled_at)->format('d/m/Y H:i'),
            
            'barber' => [
                'name' => $this->barber->name ?? 'Barbeiro',
                'avatar' => $avatarFinal,
            ],
            'service' => [
                'name' => $this->service->name ?? 'Serviço',
                'duration' => ($this->service->duration_minutes ?? 0) . ' min',
            ],
            'barbershop' => [
                'name' => $this->barber->barbershop->name ?? 'Barbearia',
            ]
        ];
    }
}