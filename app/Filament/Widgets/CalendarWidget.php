<?php

namespace App\Filament\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Js;
use Illuminate\Support\Facades\Log;

class CalendarWidget extends FullCalendarWidget
{
    protected static ?int $sort = 1;

    public function fetchEvents(array $fetchInfo): array
    {
        // Esse log você já viu, confirma que carregou
        Log::info('📅 Widget Carregado.');

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
            
            // 1. ATIVAR A SELEÇÃO (Para aparecer o quadrado amarelo)
            'selectable' => true,
            'selectMirror' => true,
            'unselectAuto' => true,

            // 2. DISPARO MANUAL (A Força Bruta)
            // Assim que você selecionar o dia, o JS manda o aviso direto pro sistema.
            'select' => new Js(<<<JS
                function(info) {
                    // Log para você conferir no F12
                    console.log('🚀 JS DISPARANDO DATA:', info.startStr);

                    // Envia para todo mundo ouvir (Tabela)
                    Livewire.dispatch('data-alterada', { date: info.startStr });
                }
            JS),
        ];
    }
}