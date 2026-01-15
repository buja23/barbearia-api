<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Filament\Resources\AppointmentResource;
use App\Filament\Widgets\CalendarWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\On; 
use Illuminate\Database\Eloquent\Builder;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    // Estado que armazena a data selecionada no calendário
    public ?string $filtroData = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Novo Agendamento'),
        ];
    }

    // Registra apenas o Calendário no topo
    protected function getHeaderWidgets(): array
    {
        return [
            CalendarWidget::class,
        ];
    }

    // OUVINTE: Captura o evento disparado pelo JavaScript do calendário
    #[On('filtrar-data')]
    public function atualizarFiltroData(string $date): void
    {
        // Se clicar na mesma data já selecionada, limpamos o filtro (mostra tudo)
        $this->filtroData = ($this->filtroData === $date) ? null : $date;
        
        // Reinicia a paginação da tabela para evitar erros de visualização
        $this->resetTable();
    }

    // FILTRO: Aplica a data na query principal do Filament
    protected function modifyQueryUsing(Builder $query): Builder
    {
        return $query
            ->when($this->filtroData, fn ($q) => $q->whereDate('scheduled_at', $this->filtroData))
            ->orderBy('scheduled_at', 'asc');
    }
}