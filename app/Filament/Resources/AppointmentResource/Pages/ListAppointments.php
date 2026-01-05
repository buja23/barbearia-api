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

    // Variável que guarda a data que o usuário clicou
    public ?string $filtroData = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CalendarWidget::class,
        ];
    }

    // 1. OUVINTE: Quando o calendário gritar "filtrar-data", cai aqui
    #[On('filtrar-data')]
    public function atualizarFiltroData(string $date): void
    {
        $this->filtroData = $date;
        $this->resetTable(); // Reinicia a paginação e atualiza
    }

    // 2. FILTRO: Aplica a data na query do banco
    protected function modifyQueryUsing(Builder $query): Builder
    {
        // Se tiver uma data selecionada, filtra. Se não, mostra tudo.
        return $query->when($this->filtroData, function ($q) {
            // Garanta que o nome da coluna é 'scheduled_at' (ou 'date_time', verifique seu banco)
            return $q->whereDate('scheduled_at', $this->filtroData);
        });
    }
}