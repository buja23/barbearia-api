<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Filament\Resources\AppointmentResource;
use App\Filament\Widgets\CalendarWidget; // Verifique se este use está assim!
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\On; 
use Illuminate\Database\Eloquent\Builder;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    public ?string $filtroData = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Novo Agendamento'),
        ];
    }

    // Define quais widgets aparecem no topo desta página específica
    protected function getHeaderWidgets(): array 
    {
        return [
            CalendarWidget::class,
        ];
    }

    // Configura o layout (1 coluna para o calendário ficar largo no topo)
    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }

    #[On('filtrar-data')]
    public function atualizarFiltroData(string $date): void
    {
        $this->filtroData = ($this->filtroData === $date) ? null : $date;
        $this->resetTable();
    }

    protected function modifyQueryUsing(Builder $query): Builder
    {
        return $query->when($this->filtroData, function ($q) {
            return $q->whereDate('scheduled_at', $this->filtroData);
        })->orderBy('scheduled_at', 'asc');
    }
}