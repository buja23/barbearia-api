<?php

namespace App\Filament\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Js;


class CalendarWidget extends FullCalendarWidget
{
    protected static ?int $sort = 1;
    
    public function fetchEvents(array $fetchInfo): array
    {
        
        $agendamentos = Appointment::query()
            ->where('scheduled_at', '>=', $fetchInfo['start'])
            ->where('scheduled_at', '<=', $fetchInfo['end'])
            ->get();

        $porDia = $agendamentos->groupBy(fn($item) => Carbon::parse($item->scheduled_at)->format('Y-m-d'));

        $eventosVisuais = [];

        foreach ($porDia as $dia => $lista) {
            $eventosVisuais[] = [
                'id' => 'bg-' . $dia,
                'title' => '',
                'start' => $dia,
                'display' => 'background',
                'classNames' => [$lista->count() >= 8 ? 'bg-evento-vermelho' : 'bg-evento-azul'],
                'allDay' => true,
            ];
        }

        return $eventosVisuais;
    }

    public function config(): array
    {
        return [
            'initialView' => 'dayGridMonth',
            'headerToolbar' => ['left' => 'prev', 'center' => 'title', 'right' => 'next'],
            
            // 1. Mantemos ligado para capturar o clique
            'selectable' => true, 
            
            // 2. REMOVEMOS O 'selectAllow' QUE ESTAVA DANDO ERRO!
            
            // 3. O Javascript limpo para gerenciar a borda
            'dateClick' => new Js(<<<JS
                function(info) {
                    // console.log('Cliquei:', info.dateStr); // Debug

                    // 1. Avisa o Livewire
                    Livewire.dispatch('filtrar-data', { date: info.dateStr });
                    
                    // 2. Limpa seleção visual anterior
                    document.querySelectorAll('.dia-selecionado').forEach(el => {
                        el.classList.remove('dia-selecionado');
                    });

                    // 3. Adiciona a classe no dia clicado
                    if (info.dayEl) {
                        info.dayEl.classList.add('dia-selecionado');
                    }
                }
            JS),
        ];
    }
}