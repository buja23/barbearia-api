<?php

namespace App\Filament\Widgets;
use App\Models\Appointment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Livewire\Attributes\On;
use Carbon\Carbon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class AppointmentsDayTable extends BaseWidget implements HasForms
{
    use InteractsWithForms;

    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public ?string $dataSelecionada = null;

    public function mount()
    {
        $this->dataSelecionada = now()->format('Y-m-d');
    }

    // O NOME DO EVENTO TEM QUE SER IGUAL AO DO CALENDÁRIO: 'data-alterada'
   #[On('data-alterada')] 
    public function atualizarData($data)
    {
        // Log para provar que chegou
        Log::info('📥 TABELA RECEBEU:', is_array($data) ? $data : ['valor' => $data]);

        if (is_array($data)) {
            $this->dataSelecionada = $data['date'] ?? array_values($data)[0];
        } else {
            $this->dataSelecionada = $data;
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(function () {
                if (! $this->dataSelecionada) return 'Selecione uma data';
                try {
                    return 'Agendamentos de ' . Carbon::parse($this->dataSelecionada)->format('d/m/Y');
                } catch (\Exception $e) {
                    return 'Data Inválida';
                }
            })
            ->query(
                Appointment::query()
                    ->when(
                        $this->dataSelecionada,
                        fn ($query) => $query->whereDate('scheduled_at', $this->dataSelecionada)
                    )
                    ->orderBy('scheduled_at', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Horário')
                    ->date('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('client_name')
                    ->label('Cliente')
                    ->getStateUsing(fn ($record) => $record->user_id ? $record->user->name : $record->client_name)
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
}