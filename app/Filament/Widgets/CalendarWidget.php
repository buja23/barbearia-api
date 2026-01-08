<?php

namespace App\Filament\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Saade\FilamentFullCalendar\Actions\CreateAction;
use App\Models\Appointment;
use Illuminate\Support\Facades\Log;

class CalendarWidget extends FullCalendarWidget
{
    protected static ?int $sort = 1;

    public function fetchEvents(array $fetchInfo): array
    {
        // ... (seu código de busca de eventos continua igual) ...
        return []; 
    }

    /**
     * O SEGREDO: Em vez de onDateSelect manual, usamos headerActions com CreateAction.
     * O plugin detecta automaticamente que existe uma CreateAction e liga o clique do dia nela.
     */
    protected function headerActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo Agendamento')
                ->mountUsing(function (\Filament\Forms\Form $form, array $arguments) {
                    // $arguments['start'] contém a data clicada!
                    $dataClicada = $arguments['start'] ?? null;
                    
                    Log::info("✅ CLIQUE VIA ACTION: " . json_encode($arguments));
                    
                    // Aqui você dispara seu evento para a tabela
                    if ($dataClicada) {
                         // Formata a data se necessário (vem como Y-m-d ou ISO)
                         $apenasData = explode('T', $dataClicada)[0];
                         $this->dispatch('data-alterada', $apenasData);
                    }
                })
                // Se você não quiser abrir modal nenhum, use ->action() vazio e ->halt()
                ->action(function() {}) 
                ->modalHeading('Agendar')
                ->modalWidth('md'),
        ];
    }

    public function config(): array
    {
        return [
            'initialView' => 'dayGridMonth',
            'headerToolbar' => ['left' => 'prev', 'center' => 'title', 'right' => 'next'],
            'selectable' => true, // Isso ativa o gatilho para a CreateAction
        ];
    }
}