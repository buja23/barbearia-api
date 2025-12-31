<?php

namespace App\Filament\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Models\Appointment;
use Carbon\Carbon;

class CalendarWidget extends FullCalendarWidget
{
    public function fetchEvents(array $fetchInfo): array
    {
        // Busca agendamentos do período visível
        $agendamentos = Appointment::query()
            ->where('scheduled_at', '>=', $fetchInfo['start'])
            ->where('scheduled_at', '<=', $fetchInfo['end'])
            ->get();

        // Agrupa por dia
        $porDia = $agendamentos->groupBy(function($item) {
            return Carbon::parse($item->scheduled_at)->format('Y-m-d');
        });

        $eventosVisuais = [];

        foreach ($porDia as $dia => $lista) {
            $quantidade = $lista->count();
            
            // Lógica: 8 ou mais cortes = Lotado (Vermelho), senão Azul
            $classeCor = $quantidade >= 8 ? 'bg-evento-vermelho' : 'bg-evento-azul';

            $eventosVisuais[] = [
                'id' => 'bg-' . $dia,
                'title' => '', // TÍTULO VAZIO para não escrever nada
                'start' => $dia,
                'display' => 'background', // Isso joga o evento para trás do número
                'classNames' => [$classeCor], // Aplica nossa classe CSS de bolinha
                'allDay' => true,
            ];
        }

        return $eventosVisuais;
    }
}