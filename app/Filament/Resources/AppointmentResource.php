<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Barber;
use App\Models\OpeningHour;
use Carbon\Carbon;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
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
                Select::make('barber_id')
                    ->relationship('barber', 'name')
                    ->required()
                    ->live()
                    ->label('Barbeiro'),

                Select::make('service_id')
                    ->relationship('service', 'name')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        $service = Service::find($state);
                        if ($service) {
                            $set('total_price', $service->price);
                        }
                    })
                    ->label('Serviço'),

                DateTimePicker::make('scheduled_at')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y H:i')
                    ->seconds(false)
                    ->minutesStep(15) // Facilita a escolha de horários quebrados
                    ->label('Data e Hora')
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                            $date = Carbon::parse($value);
                            $barberId = $get('barber_id');

                            if (!$barberId) return;

                            $barber = Barber::find($barberId);
                            $dayOfWeek = $date->dayOfWeek; // 0 (Dom) a 6 (Sab)
                            $timeRequested = $date->format('H:i:s');

                            // 1. Validar se a Barbearia está aberta
                            $openingHour = OpeningHour::where('barbershop_id', $barber->barbershop_id)
                                ->where('day_of_week', $dayOfWeek)
                                ->first();

                            if (!$openingHour || $openingHour->is_closed) {
                                $fail("A barbearia está fechada neste dia.");
                                return;
                            }

                            if ($timeRequested < $openingHour->opening_time || $timeRequested > $openingHour->closing_time) {
                                $fail("Fora do horário de funcionamento ({$openingHour->opening_time} às {$openingHour->closing_time}).");
                            }

                            // 2. Validar Almoço do Barbeiro
                            if ($barber->lunch_start && $barber->lunch_end) {
                                if ($timeRequested >= $barber->lunch_start && $timeRequested < $barber->lunch_end) {
                                    $fail("O barbeiro está em horário de almoço ({$barber->lunch_start} às {$barber->lunch_end}).");
                                }
                            }
                        },
                    ]),

                TextInput::make('total_price')
                    ->numeric()
                    ->prefix('R$')
                    ->readOnly()
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