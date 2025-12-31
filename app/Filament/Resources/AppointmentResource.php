<?php
namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use App\Models\Service;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('barber_id')
                    ->relationship('barber', 'name')
                    ->required()
                    ->label('Barbeiro'),

                Select::make('service_id')
                    ->relationship('service', 'name')
                    ->required()
                    ->live() // IMPORTANTE: Torna o campo reativo
                    ->afterStateUpdated(function ($state, Set $set) {
                        // Quando escolhe o serviço, busca o preço dele e preenche o campo 'total_price'
                        $service = Service::find($state);
                        if ($service) {
                            $set('total_price', $service->price);
                        }
                    })
                    ->label('Serviço'),

                DateTimePicker::make('scheduled_at')
                    ->required()
                    ->seconds(false)
                    ->label('Data e Hora'),

                TextInput::make('total_price')
                    ->numeric()
                    ->prefix('R$')
                    ->readOnly() // O dono não edita o preço na mão (ou remove isso se quiser permitir desconto)
                    ->required()
                    ->label('Valor'),

                Select::make('status')
                    ->options([
                        'pending'   => 'Pendente',
                        'confirmed' => 'Confirmado',
                        'completed' => 'Concluído',
                        'cancelled' => 'Cancelado',
                    ])
                    ->default('confirmed')
                    ->required(),

                // Seção Cliente
                TextInput::make('client_name')
                    ->label('Nome do Cliente (Avulso)')
                    ->placeholder('Ex: João da Silva'),

                TextInput::make('client_phone')
                    ->label('Telefone')
                    ->mask('(99) 99999-9999'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 1. Cliente (Pega direto da coluna client_name)
                // Se você quiser pegar do usuário logado, troque por 'user.name'
                TextColumn::make('cliente')
                    ->label('Cliente')
                    ->getStateUsing(fn($record) =>
                        $record->client_name ?? $record->user?->name
                    )
                    ->searchable(),

                // 2. Barbeiro (Pega a relação 'barber' e o campo 'name' dele)
                // Certifique-se que na tabela 'barbers' a coluna do nome é 'name'
                TextColumn::make('barber.name')
                    ->label('Barbeiro')
                    ->sortable(),

                // 3. Serviço (Pega a relação 'service' e o campo 'name' dele)
                TextColumn::make('service.name')
                    ->label('Serviço'),

                // 4. Data e Hora (O seu campo é 'scheduled_at')
                TextColumn::make('scheduled_at')
                    ->label('Data e Hora')
                    ->dateTime('d/m/Y H:i') // Formatação brasileira
                    ->sortable(),

                // 5. Preço Total
                TextColumn::make('total_price')
                    ->label('Preço')
                    ->money('BRL'), // Formata como R$

                // 6. Status
                TextColumn::make('status')
                    ->badge()                                                        // Isso transforma o texto naquele botãozinho arredondado
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)) // Deixa a primeira letra maiúscula (pending -> Pending)
                    ->color(fn(string $state): string => match ($state) {
                        'pending'   => 'warning', // Amarelo (Atenção)
                        'confirmed' => 'success', // Verde (Sinal verde/Futuro)
                        'completed' => 'info',    // Azul (Aqui a mágica! Diferencia do pendente)
                        'cancelled' => 'danger',  // Vermelho (Erro/Cancelado)
                        default     => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

            ])
            ->defaultSort('scheduled_at', 'desc') // Ordena do mais recente para o antigo
            ->filters([
                SelectFilter::make('status')
                    ->label('Filtrar por Status')
                    ->options([
                        'pending'   => 'Pendente',
                        'confirmed' => 'Confirmado',
                        'completed' => 'Concluído',
                        'cancelled' => 'Cancelado',
                    ]),

                // 2. Filtro de Data
                Filter::make('data_agendamento')
                    ->form([
                        DatePicker::make('data_inicial')->label('De'),
                        DatePicker::make('data_final')->label('Até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['data_inicial'],
                                fn(Builder $query, $date) => $query->whereDate('date_time', '>=', $date),
                            )
                            ->when(
                                $data['data_final'],
                                fn(Builder $query, $date) => $query->whereDate('date_time', '<=', $date),
                            );
                    }),

                SelectFilter::make('periodo')
                    ->label('Período')
                    ->placeholder('Todo o período') // Opção padrão (sem filtro)
                    ->options([
                        'hoje'   => 'Hoje',
                        'semana' => 'Esta Semana',
                        'mes'    => 'Este Mês',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        // TROQUE 'scheduled_at' PELO NOME REAL DA SUA COLUNA DE DATA
                        $colunaData = 'scheduled_at';

                        return match ($data['value']) {
                            'hoje'   => $query->whereDate($colunaData, Carbon::today()),

                            'semana' => $query->whereBetween($colunaData, [
                                Carbon::now()->startOfWeek(),
                                Carbon::now()->endOfWeek(),
                            ]),

                            'mes'    => $query->whereMonth($colunaData, Carbon::now()->month)
                                ->whereYear($colunaData, Carbon::now()->year),

                            default  => $query,
                        };
                    }),
            ])

            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    BulkAction::make('alterarStatus')
                        ->label('Atualizar Status')
                        ->icon('heroicon-o-check-circle')
                        ->color('warning') // Botão amarelo pra chamar atenção
                        ->requiresConfirmation()
                        ->form([
                            \Filament\Forms\Components\Select::make('status')
                                ->label('Novo Status')
                                ->options([
                                    'confirmed' => 'Confirmado',
                                    'completed' => 'Concluído',
                                    'cancelled' => 'Cancelado',
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            // Atualiza todos os selecionados de uma vez
                            $records->each(fn($record) => $record->update(['status' => $data['status']]));
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit'   => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}
