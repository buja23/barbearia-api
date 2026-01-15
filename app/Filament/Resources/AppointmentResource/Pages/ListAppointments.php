<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Filament\Resources\AppointmentResource;
use App\Filament\Widgets\CalendarWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\On; 

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Novo Agendamento'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CalendarWidget::class,
        ];
    }

    // 🚀 O PULO DO GATO: Atualiza o filtro oficial da tabela
    #[On('filtrar-data')]
    public function atualizarFiltroData(string $date): void
    {
        // Se a data clicada for a mesma que já está no filtro, nós limpamos (Toggle)
        if (($this->tableFilters['data_agendamento']['data_inicial'] ?? null) === $date) {
            $this->tableFilters['data_agendamento'] = [
                'data_inicial' => null,
                'data_final' => null,
            ];
        } else {
            // Injeta a data no filtro 'data_agendamento' (inicial e final iguais para filtrar o dia exato)
            $this->tableFilters['data_agendamento'] = [
                'data_inicial' => $date,
                'data_final' => $date,
            ];
        }

        // Reseta a página para a 1 para evitar erros de paginação
        $this->resetPage();
    }
}