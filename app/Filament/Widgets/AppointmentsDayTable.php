<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Livewire\Attributes\On;

class AppointmentsDayTable extends BaseWidget
{
    public $dataSelecionada;

    // Inicia com a data de hoje
    public function mount()
    {
        $this->dataSelecionada = now()->toDateString();
    }

    // Escuta o evento do calendário
    #[On('data-alterada')]
    public function atualizarData($data)
    {
        $this->dataSelecionada = $data;
    }

    protected static ?string $heading = 'Agendamentos do Dia';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Filtra dinamicamente pela data selecionada no calendário
                Appointment::query()->whereDate('scheduled_at', $this->dataSelecionada)
            )
            ->columns([
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Hora')
                    ->dateTime('H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('client_name')
                    ->label('Cliente')
                    ->description(fn($record) => $record->user?->name),
                Tables\Columns\TextColumn::make('service.name')->label('Serviço'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'info' => 'completed',
                        'danger' => 'cancelled',
                    ]),
            ]);
    }
}