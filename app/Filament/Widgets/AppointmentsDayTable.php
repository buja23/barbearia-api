<?php

namespace App\Filament\Widgets; // Ajustei o namespace para bater com sua pasta

use App\Models\Appointment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Livewire\Attributes\On;
use Carbon\Carbon;
use Illuminate\Contracts\View\View; // <--- 1. Importante: Adicionamos isso

class AppointmentsDayTable extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public ?string $dataSelecionada = null;

    public function mount()
    {
        $this->dataSelecionada = now()->format('Y-m-d');
    }

  #[On('data-alterada')]
    public function atualizarData($date)
    {
        $this->dataSelecionada = $date;
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
                    ->whereDate('scheduled_at', $this->dataSelecionada)
                    ->orderBy('scheduled_at', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Horário')
                    ->date('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('client_name')
                    ->label('Cliente')
                    ->getStateUsing(function ($record) {
                        return $record->user_id ? $record->user->name : $record->client_name;
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Valor')
                    ->money('BRL'),

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

    // <--- 2. A CORREÇÃO PRINCIPAL ESTÁ AQUI EMBAIXO:
    public function render(): View
    {
       return view('filament.widgets.appointments-day-table');
    }
}