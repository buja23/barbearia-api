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
            
            'selectable' => true, // Importante para o cursor
            'selectMirror' => true,
            
            // Aqui fica APENAS a lógica do clique
            'dateClick' => new Js(<<<JS
                function(info) {
                    // 1. Alerta de Debug (remova depois que funcionar)
                    alert('DATA CLICADA: ' + info.dateStr);

                    // 2. Dispara o evento para a tabela
                    Livewire.dispatch('data-alterada', { date: info.dateStr });

                    // 3. Aplica a classe visual (definida lá no AdminPanelProvider)
                    document.querySelectorAll('.dia-selecionado').forEach(el => el.classList.remove('dia-selecionado'));
                    
                    // Adiciona a classe no elemento de número (para ficar redondo) ou no frame
                    if (info.dayEl) {
                        info.dayEl.classList.add('dia-selecionado');
                    }
                }
            JS),
        ];
    }
}