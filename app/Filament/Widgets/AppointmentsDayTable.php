<?php

namespace App\Filament\Widgets;

use App\Models\Appointment; // Seu model de agendamento
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Livewire\Attributes\On; // Importante para o evento
use Carbon\Carbon;

class AppointmentsDayTable extends BaseWidget
{
    protected static ?int $sort = 2;
    // Define que esse widget ocupa a largura total abaixo do calendário
    protected int | string | array $columnSpan = 'full';

    protected $listeners = ['data-alterada' => 'atualizarData'];

    // Variável para guardar a data selecionada. Começa com 'hoje'.
    public ?string $dataSelecionada = null;

    public function mount()
    {
        // Ao iniciar, define a data como hoje
        $this->dataSelecionada = now()->format('Y-m-d');
    }

    // LISTENER: Quando o calendário gritar 'data-alterada', essa função roda
    #[On('data-alterada')]
    public function atualizarData($date)
    {
        // A BOMBA DE TESTE:
        // Se o PHP receber o clique, ele vai parar tudo e mostrar a data na tela.
        dd("CHEGOU AQUI! A data é: " . $date); 

        $this->dataSelecionada = $date;
        $this->resetTable();
    }

public function table(Table $table): Table
    {
        return $table
            ->heading(
                $this->dataSelecionada 
                ? 'Agendamentos de ' . Carbon::parse($this->dataSelecionada)->format('d/m/Y')
                : 'Selecione uma data'
            )
            ->query(
                Appointment::query()
                    // CORREÇÃO: Usando o nome real da coluna do seu banco
                    ->whereDate('scheduled_at', $this->dataSelecionada)
                    ->orderBy('scheduled_at', 'asc')
            )
            ->columns([
                // 1. Horário (Extraído do scheduled_at)
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Horário')
                    ->date('H:i') // Formata para mostrar apenas a hora
                    ->sortable(),
                
                // 2. Cliente (Lógica mista: App ou Manual)
                Tables\Columns\TextColumn::make('client_name')
                    ->label('Cliente')
                    // Se tiver user_id, mostra o nome do user, senão mostra o nome manual
                    ->getStateUsing(function ($record) {
                        return $record->user_id ? $record->user->name : $record->client_name;
                    })
                    ->searchable(),

                // 3. Preço
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Valor')
                    ->money('BRL'),

                // 4. Status (Traduzindo as cores do seu schema)
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(), 
            ])
            ->paginated(false); 
    }
}