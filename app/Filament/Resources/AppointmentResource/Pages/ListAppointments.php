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

    protected function getHeaderActions(): array
    {
        return [
            // Botão de Limpar (Só aparece quando tem data selecionada)
            Actions\Action::make('limpar_filtros')
                ->label('Limpar Data')
                ->icon('heroicon-m-x-mark') // Ícone de fechar
                ->color('gray')
                ->outlined() // Borda fina para não brigar com o botão principal
                ->visible(fn () => ! empty($this->tableFilters['data_agendamento']['data_inicial'] ?? null))
                ->action(function () {
                    // 1. Limpa o filtro de data
                    $this->tableFilters['data_agendamento'] = null;
                    
                    // 2. Avisa o Calendário para remover a bolinha preta
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

    public function getTabs(): array
    {
        // Queries auxiliares para os contadores (Badge) ficarem rápidos
        $query = $this->getModel()::query();

        return [
            // === DESTAQUE 1: O OPERACIONAL (O que tenho pra fazer?) ===
            'agenda' => Tab::make('Agenda Aberta')
                ->icon('heroicon-o-calendar-days')
                ->badge($this->getModel()::whereIn('status', ['pending', 'confirmed'])->count())
                ->badgeColor('info') // Azul para foco
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->orderBy('scheduled_at', 'asc') // Os mais próximos primeiro
                ),

            // === DESTAQUE 2: O FINANCEIRO/RESULTADO (O que já aconteceu?) ===
            'historico' => Tab::make('Histórico (Pagos/Faltas)')
                ->icon('heroicon-o-archive-box')
                ->badgeColor('success') // Verde para sucesso
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where(function ($q) {
                        $q->where('payment_status', 'approved') // Dinheiro no bolso
                          ->orWhere('status', 'no_show');       // Ou furo
                    })
                    ->orderBy('scheduled_at', 'desc') // Do mais recente para trás
                ),

            // === FILTROS ESPECÍFICOS (Abaixo/Depois dos principais) ===
            'confirmados' => Tab::make('Apenas Confirmados')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'confirmed')),

           'cancelados' => Tab::make('Cancelados') // <--- NOVA ABA PARA O QUE VOCÊ PROCURA
                ->icon('heroicon-o-x-mark')
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),

            'faltas' => Tab::make('Não Compareceu (No-Show)')
                ->icon('heroicon-o-eye-slash') // Ícone mais adequado para "não visto"
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'no_show')),

            'todos' => Tab::make('Todos')
                ->icon('heroicon-o-list-bullet'),
        ];
    }

    // 🚀 INTEGRAÇÃO COM CALENDÁRIO
    #[On('filtrar-data')]
    public function atualizarFiltroData(string $date): void
    {
        // Lógica de Toggle: Se clicar na mesma data, limpa o filtro. Se for nova, aplica.
        if (($this->tableFilters['data_agendamento']['data_inicial'] ?? null) === $date) {
            $this->tableFilters['data_agendamento'] = [
                'data_inicial' => null,
                'data_final'   => null, // Remove filtro de data
            ];
        } else {
            // Aplica filtro: Data Inicial e Final iguais para pegar APENAS aquele dia
            $this->tableFilters['data_agendamento'] = [
                'data_inicial' => $date,
                'data_final'   => null, // O filtro na Resource já trata o >= se o final for null, ou podemos forçar igualdade
            ];
            // OBS: Verifique se no AppointmentResource o filtro 'data_agendamento' 
            // está preparado para receber apenas data_inicial ou se precisa dos dois.
            // Se precisar ser o dia exato, o ideal é atualizar o filtro na Resource para whereDate('scheduled_at', $date)
        }

        $this->resetPage();
    }
}