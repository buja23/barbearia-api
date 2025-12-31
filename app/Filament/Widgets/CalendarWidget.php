<?php

namespace App\Filament\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Models\Appointment;

class CalendarWidget extends FullCalendarWidget
{
    /**
     * Busca os eventos no banco de dados dentro do intervalo visível no calendário
     */
    public function fetchEvents(array $fetchInfo): array
    {
        return Appointment::query()
            // Filtra para pegar apenas o que está na tela do usuário (performance)
            ->where('scheduled_at', '>=', $fetchInfo['start'])
            ->where('scheduled_at', '<=', $fetchInfo['end'])
            ->get()
            ->map(
                fn (Appointment $event) => [
                    'id'    => $event->id,
                    // Título: "Nome do Cliente - Serviço"
                    'title' => "{$event->cliente_nome} - {$event->servico}", 
                    'start' => $event->scheduled_at,
                    // Se você não tiver data de fim, assume 1 hora de duração
                    'end'   => $event->scheduled_at->addHour(), 
                    
                    // Aqui aplicamos as CORES VISUAIS do seu protótipo
                    'color' => match ($event->status) {
                        'pending'   => '#eab308', // Amarelo (Warning)
                        'confirmed' => '#22c55e', // Verde (Success)
                        'completed' => '#3b82f6', // Azul (Info)
                        'cancelled' => '#ef4444', // Vermelho (Danger)
                        default     => '#6b7280', // Cinza
                    },
                    
                    // Link para abrir a edição ao clicar
                    'url' => \App\Filament\Resources\AppointmentResource::getUrl('edit', ['record' => $event]),
                    'shouldOpenUrlInNewTab' => false,
                ]
            )
            ->all();
    }
}