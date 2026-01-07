<?php

namespace App\Filament\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CalendarWidget extends FullCalendarWidget
{
    protected static ?int $sort = 1;

    // Propriedade para guardar qual dia deve ficar amarelo
    public string $dataSelecionada = '';

    /**
     * FUNÇÃO DE CLIQUE (Nativa do Plugin)
     * Quando você clica no dia, o plugin chama esta função automaticamente.
     */
    public function onDateClick(array $date, bool $allDay, array $view, array $resource = null): void
    {
        // 1. Pega a data clicada (o plugin manda como array ['date' => '...'])
        $dataClicada = $date['dateStr'] ?? $date['date'] ?? null;

        if ($dataClicada) {
            Log::info("🖱️ CLIQUE RECEBIDO: {$dataClicada}");

            // 2. Salva a data na propriedade para pintar de amarelo depois
            $this->dataSelecionada = $dataClicada;

            // 3. Avisa a Tabela
            $this->dispatch('data-alterada', $dataClicada);

            // 4. Força o calendário a se redesenhar (para aparecer o amarelo)
            $this->refreshEvents();
        }
    }

    public function fetchEvents(array $fetchInfo): array
    {
        $eventosVisuais = [];

        // === PARTE 1: BUSCAR AGENDAMENTOS DO BANCO ===
        $agendamentos = Appointment::query()
            ->where('scheduled_at', '>=', $fetchInfo['start'])
            ->where('scheduled_at', '<=', $fetchInfo['end'])
            ->get();

        $porDia = $agendamentos->groupBy(fn($item) => Carbon::parse($item->scheduled_at)->format('Y-m-d'));

        foreach ($porDia as $dia => $lista) {
            $eventosVisuais[] = [
                'id' => 'bolinha-' . $dia,
                'title' => '', // Sem título, só a bolinha
                'start' => $dia,
                'display' => 'background',
                'classNames' => [$lista->count() >= 8 ? 'bg-evento-vermelho' : 'bg-evento-azul'],
                'allDay' => true,
            ];
        }

        // === PARTE 2: DESENHAR O QUADRADO AMARELO (SELEÇÃO) ===
        // Se tivermos uma data selecionada, criamos um "evento" amarelo nela
        if ($this->dataSelecionada) {
            $eventosVisuais[] = [
                'id' => 'selecao-atual',
                'title' => '',
                'start' => $this->dataSelecionada,
                'display' => 'background',
                'classNames' => ['dia-selecionado-php'], // Classe especial
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
            
            // IMPORTANTE: Desligamos a seleção nativa (selectable: false)
            // Agora confiamos 100% no onDateClick que é mais estável.
            'selectable' => false,
        ];
    }
}