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
            Actions\CreateAction::make()->label('Novo Agendamento'),
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
        return [
            'agenda' => Tab::make('Agenda Aberta')
                ->icon('heroicon-o-calendar')
                ->badge(
                    $this->getModel()::whereIn('status', ['pending', 'confirmed'])->count()
                )
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['pending', 'confirmed'])),

            'historico' => Tab::make('Histórico (Pagos/Faltas)')
                ->icon('heroicon-o-archive-box')
                // 👇 AQUI ESTÁ A MUDANÇA SOLICITADA
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where(function ($q) {
                        $q->where('payment_status', 'approved') // 1. Pagamento Confirmado
                          ->orWhere('status', 'no_show');       // 2. OU Faltou
                    })
                    ->orderBy('scheduled_at', 'desc') // Ordena do mais recente para o antigo
                ),
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
                'data_final'   => null,
            ];
        } else {
            // Injeta a data no filtro 'data_agendamento' (inicial e final iguais para filtrar o dia exato)
            $this->tableFilters['data_agendamento'] = [
                'data_inicial' => $date,
                'data_final'   => $date,
            ];
        }

        // Reseta a página para a 1 para evitar erros de paginação
        $this->resetPage();
    }

    public function hydrate()
    {
        // Método Senior: Toda vez que o Livewire "acorda" (no polling), verificamos
        // se tem algum modal aberto precisando de verificação.
        // Como estamos numa lista, verificar todos seria pesado.
        // Neste estágio, o polling apenas atualiza o banco.
        // Para checar a API a cada 5s, o ideal é o Webhook.

        // MAS, para teste imediato, vamos deixar o wire:poll apenas renderizar.
        // O status só mudará se o Webhook bater ou se fizermos uma checagem manual.
    }
}
