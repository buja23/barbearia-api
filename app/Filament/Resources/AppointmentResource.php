<?php
namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use App\Models\Barber;
use App\Services\BookingService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
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

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Agendamentos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Agendamento Inteligente')
                    ->schema([
                        Select::make('barber_id')
                            ->relationship('barber', 'name')
                            ->required()
                            ->live() // Essencial para disparar a busca de horários
                            ->label('Barbeiro'),

                        DatePicker::make('appointment_date')
                            ->label('Data')
                            ->required()
                            ->live()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->dehydrated(false) // Campo virtual, não salva direto no banco
                            ->afterStateHydrated(fn($state, $record, Set $set) =>
                                $record ? $set('appointment_date', $record->scheduled_at->format('Y-m-d')) : null
                            ),

                        Select::make('appointment_time')
                            ->label('Horários Livres')
                            ->required()
                            ->options(function (Get $get, BookingService $service) {
                                $barberId  = $get('barber_id');
                                $date      = $get('appointment_date');
                                $serviceId = $get('service_id'); // Pegamos o ID do serviço

                                if (! $barberId || ! $date || ! $serviceId) {
                                    return ['' => 'Selecione barbeiro, data e serviço'];
                                }

                                $barber = Barber::find($barberId);
                                // Passamos o serviceId para o cálculo de duração
                                $slots = $service->getAvailableSlots($barber, $date, $serviceId);

                                return collect($slots)->combine($slots)->toArray();
                            })
                            ->live() // Garante que atualiza quando o service_id mudar
                            ->dehydrated(false)
                            ->afterStateHydrated(fn($state, $record, Set $set) =>
                                $record ? $set('appointment_time', $record->scheduled_at->format('H:i')) : null
                            )
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                $date = $get('appointment_date');

                                if ($date && $state) {
                                    // FIX: Usamos o Carbon para extrair APENAS a data e juntar com a hora limpa
                                    $formattedDate = \Carbon\Carbon::parse($date)->format('Y-m-d');
                                    $set('scheduled_at', "{$formattedDate} {$state}:00");
                                }
                            }),

                        // Este é o campo real que o banco de dados espera
                        Hidden::make('scheduled_at')->required(),
                    ])->columns(3),

                Section::make('Serviço e Valor')
                    ->schema([
                        Select::make('service_id')
                            ->relationship('service', 'name')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $service = \App\Models\Service::find($state);
                                if ($service) {
                                    $set('total_price', $service->price);
                                }
                            })
                            ->label('Serviço'),

                        TextInput::make('total_price')
                            ->numeric()
                            ->prefix('R$')
                            ->readOnly()
                            ->label('Valor'),
                    ])->columns(2),

                Select::make('status')
                    ->options([
                        'pending'   => 'Pendente',
                        'confirmed' => 'Confirmado',
                        'completed' => 'Concluído',
                        'cancelled' => 'Cancelado',
                    ])
                    ->default('confirmed')
                    ->required(),

                TextInput::make('client_name')
                    ->label('Nome do Cliente (Avulso)')
                    ->placeholder('Ex: João da Silva'),

                TextInput::make('client_phone')
                    ->label('Telefone')
                    ->mask('(99) 99999-9999'),
            ]);
    }

    // ... Restante do código (Table, Filters, etc) permanece igual ao seu original
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cliente')
                    ->label('Cliente')
                    ->getStateUsing(fn($record) => $record->client_name ?? $record->user?->name)
                    ->searchable(),

                TextColumn::make('barber.name')
                    ->label('Barbeiro')
                    ->sortable(),

                TextColumn::make('service.name')
                    ->label('Serviço'),

                TextColumn::make('scheduled_at')
                    ->label('Data e Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('total_price')
                    ->label('Preço')
                    ->money('BRL'),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->color(fn(string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'confirmed' => 'success',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Filtrar por Status')
                    ->options([
                        'pending'   => 'Pendente',
                        'confirmed' => 'Confirmado',
                        'completed' => 'Concluído',
                        'cancelled' => 'Cancelado',
                    ]),

                Filter::make('data_agendamento')
                    ->form([
                        DatePicker::make('data_inicial')->label('De'),
                        DatePicker::make('data_final')->label('Até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['data_inicial'], fn($query, $date) => $query->whereDate('scheduled_at', '>=', $date))
                            ->when($data['data_final'], fn($query, $date) => $query->whereDate('scheduled_at', '<=', $date));
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
                        ->action(function (Collection $records, array $data) {
                            $records->each(fn($record) => $record->update(['status' => $data['status']]));
                        })
                        ->form([
                            Select::make('status')
                                ->options([
                                    'confirmed' => 'Confirmado',
                                    'completed' => 'Concluído',
                                    'cancelled' => 'Cancelado',
                                ])->required(),
                        ]),
                ]),
            ]);
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
