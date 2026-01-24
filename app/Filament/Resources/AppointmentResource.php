<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use App\Services\BookingService;
use App\Services\PaymentService;
use Carbon\Carbon;
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
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AppointmentResource extends Resource
{
    protected static ?string $model            = Appointment::class;
    protected static ?string $navigationIcon   = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel  = 'Agendamentos';
    protected static ?string $modelLabel       = 'Agendamento';
    protected static ?string $pluralModelLabel = 'Agendamentos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Agendamento Inteligente')
                    ->description('Selecione o barbeiro e a data para visualizar horários disponíveis.')
                    ->schema([
                        Select::make('barber_id')
                            ->relationship('barber', 'name')
                            ->required()
                            ->live()
                            ->label('Barbeiro'),

                        DatePicker::make('appointment_date')
                            ->label('Data do Corte')
                            ->required()
                            ->live()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection()
                            ->dehydrated(false),

                        Select::make('appointment_time')
                            ->label('Horários Livres')
                            ->required()
                            ->options(function (Get $get, BookingService $service) {
                                $barberId  = $get('barber_id');
                                $date      = $get('appointment_date');
                                $serviceId = $get('service_id');

                                if (! $barberId || ! $date || ! $serviceId) {
                                    return [];
                                }

                                $barber = \App\Models\Barber::find($barberId);
                                return collect($service->getAvailableSlots($barber, $date, $serviceId))
                                    ->mapWithKeys(fn($slot) => [$slot => $slot])
                                    ->toArray();
                            })
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                $date = $get('appointment_date');
                                if ($date && $state) {
                                    $cleanDate = Carbon::parse($date)->format('Y-m-d');
                                    $set('scheduled_at', "{$cleanDate} {$state}:00");
                                }
                            }),

                        Hidden::make('scheduled_at')->required(),
                    ])->columns(3),

                Section::make('Detalhes do Serviço')
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
                            ->label('Valor do Serviço'),

                        Select::make('status')
                            ->label('Status do Agendamento')
                            ->options([
                                'pending'   => 'Pendente',
                                'confirmed' => 'Confirmado',
                                'cancelled' => 'Cancelado',
                                'completed' => 'Concluído',
                            ])
                            ->default('confirmed')
                            ->required(),
                    ])->columns(3),

                Section::make('Informações do Cliente')
                    ->schema([
                        TextInput::make('client_name')
                            ->label('Nome do Cliente')
                            ->placeholder('Ex: João da Silva'),

                        TextInput::make('client_phone')
                            ->label('Telefone/WhatsApp')
                            ->mask('(99) 99999-9999')
                            ->tel(),
                    ])->columns(2),
            ]);
    }

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
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('scheduled_at')
                    ->label('Data e Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('total_price')
                    ->label('Preço')
                    ->money('BRL'),

                // Coluna oculta para controle do barbeiro
                TextColumn::make('barber_commission_value')
                    ->label('Minha Comissão')
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // --- COLUNA VISUAL DE PAGAMENTO ---
                TextColumn::make('payment_status')
                    ->label('Pagamento')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'approved' => 'Aprovado',
                        'pending'  => 'Pendente',
                        'rejected' => 'Recusado',
                        default    => 'Aguardando',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success', // Verde
                        'pending'  => 'warning', // Amarelo
                        'rejected' => 'danger',  // Vermelho
                        default    => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'approved' => 'heroicon-o-check-circle',
                        'pending'  => 'heroicon-o-clock',
                        'rejected' => 'heroicon-o-x-circle',
                        default    => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable(),
                
                // Status do Agendamento
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending'   => 'Pendente',
                        'confirmed' => 'Confirmado',
                        'cancelled' => 'Cancelado',
                        'completed' => 'Concluído',
                        'no_show'   => 'Faltou',
                        default     => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'confirmed' => 'info',
                        'cancelled' => 'danger',
                        'completed' => 'success',
                        'no_show'   => 'danger',
                        default     => 'gray',
                    }),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending'   => 'Pendente',
                        'confirmed' => 'Confirmado',
                        'completed' => 'Concluído',
                        'cancelled' => 'Cancelado',
                        'no_show'   => 'Faltou',
                    ]),

                SelectFilter::make('barber_id')
                    ->label('Barbeiro')
                    ->relationship('barber', 'name'),

                Filter::make('data_agendamento')
                    ->form([
                        DatePicker::make('data_inicial')->label('Desde'),
                        DatePicker::make('data_final')->label('Até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['data_inicial'], fn($query, $date) => $query->whereDate('scheduled_at', '>=', $date))
                            ->when($data['data_final'], fn($query, $date) => $query->whereDate('scheduled_at', '<=', $date));
                    }),
            ])
            // --- AÇÕES INDIVIDUAIS (POR LINHA) ---
            ->actions([
                Tables\Actions\EditAction::make(),

                // GRUPO DE PAGAMENTO (Botão ou Check Visual)
                Tables\Actions\ActionGroup::make([
                    
                    // 1. Botão de PAGAR (Aparece se Pendente/Recusado)
                    Action::make('pay')
                        ->label('Pagar Pix')
                        ->icon('heroicon-o-qr-code')
                        ->color('warning')
                        ->visible(fn(Appointment $record) => $record->payment_status !== 'approved')
                        ->modalHeading('Finalizar Pagamento')
                        ->modalSubmitAction(false) 
                        ->modalCancelAction(fn($action) => $action->label('Fechar'))
                        ->modalContent(function (Appointment $record, PaymentService $service) {
                            if (empty($record->pix_copy_paste) || $record->payment_status === 'cancelled') {
                                $result = $service->createPixPayment($record);

                                if (! $result['success']) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Erro no Mercado Pago')
                                        ->body($result['error'] ?? 'Erro desconhecido')
                                        ->danger()
                                        ->send();
                                    return view('filament.payments.error-modal', ['error' => $result['error']]);
                                }
                                $record->refresh();
                            }
                            return view('filament.payments.pix-modal', ['record' => $record]);
                        }),

                    // 2. Indicador Visual (Aparece se APROVADO)
                    Action::make('paid_indicator')
                        ->label('Pago')
                        ->icon('heroicon-s-check-badge')
                        ->color('success')
                        ->disabled() 
                        ->visible(fn(Appointment $record) => $record->payment_status === 'approved'),

                ])->link(), // Link deixa os ícones lado a lado

                // Botão de FALTOU
                Action::make('noShow')
                    ->label('Faltou')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(Appointment $record) => $record->status === 'confirmed')
                    ->action(function (Appointment $record) {
                        $record->update(['status' => 'no_show']);
                        if ($record->user?->subscription) {
                            $record->user->subscription->increment('uses_this_month');
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Marcado como No-Show')
                            ->danger()
                            ->send();
                    }),
            ])
            // --- AÇÕES EM MASSA (VÁRIOS DE UMA VEZ) ---
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    BulkAction::make('alterarStatus')
                        ->label('Alterar Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Select::make('status')
                                ->label('Novo Status')
                                ->options([
                                    'confirmed' => 'Confirmado',
                                    'completed' => 'Concluído',
                                    'cancelled' => 'Cancelado',
                                ])->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each(fn($record) => $record->update(['status' => $data['status']]));
                        }),
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

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\CalendarWidget::class,
        ];
    }
}