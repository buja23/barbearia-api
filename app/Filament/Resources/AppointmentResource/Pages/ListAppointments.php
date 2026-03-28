<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Filament\Resources\AppointmentResource;
use App\Filament\Widgets\CalendarWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    // Propriedade reativa para filtro de data vindo do calendário
    public ?string $calendarDate = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('limpar_filtros')
                ->label('Limpar Data')
                ->icon('heroicon-m-x-mark')
                ->color('gray')
                ->outlined()
                ->visible(fn () => ! empty($this->calendarDate))
                ->action(function () {
                    $this->calendarDate = null;
                    $this->tableFilters['data_agendamento'] = ['data_inicial' => null, 'data_final' => null];
                    $this->dispatch('limpar-calendario');
                }),

            Actions\CreateAction::make()
                ->label('Novo Agendamento'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CalendarWidget::class,
        ];
    }

    // Segunda camada de garantia: aplica o filtro diretamente na query base
    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->when($this->calendarDate, fn ($q) => $q->whereDate('scheduled_at', $this->calendarDate));
    }

    public function getTabs(): array
    {
        return [
            'agenda' => Tab::make('Agenda Aberta')
                ->icon('heroicon-o-calendar-days')
                ->badge(
                    $this->getModel()::whereIn('status', ['pending', 'confirmed'])
                        ->when(filament()->getTenant(), fn ($q, $t) => $q->where('barbershop_id', $t->id))
                        ->count()
                )
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->orderBy('scheduled_at', 'asc')
                ),

            'historico' => Tab::make('Histórico (Pagos/Faltas)')
                ->icon('heroicon-o-archive-box')
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where(function ($q) {
                        $q->where('payment_status', 'approved')
                          ->orWhere('status', 'no_show');
                    })
                    ->orderBy('scheduled_at', 'desc')
                ),

            'confirmados' => Tab::make('Apenas Confirmados')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'confirmed')),

            'cancelados' => Tab::make('Cancelados')
                ->icon('heroicon-o-x-mark')
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'canceled')),

            'faltas' => Tab::make('Não Compareceu (No-Show)')
                ->icon('heroicon-o-eye-slash')
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'no_show')),

            'todos' => Tab::make('Todos')
                ->icon('heroicon-o-list-bullet'),
        ];
    }

    // Integração com Calendário
    #[On('filtrar-data')]
    public function atualizarFiltroData(string $date): void
    {
        if ($this->calendarDate === $date) {
            // Toggle: clicou no mesmo dia, limpa
            $this->calendarDate = null;
            $this->tableFilters['data_agendamento'] = ['data_inicial' => null, 'data_final' => null];
        } else {
            $this->calendarDate = $date;
            $this->tableFilters['data_agendamento'] = ['data_inicial' => $date, 'data_final' => null];
        }

        $this->resetPage();
    }
}