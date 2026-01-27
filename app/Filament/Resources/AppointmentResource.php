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
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
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
                            ->dehydrated(false), // Não salva no banco, serve só pra lógica

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
        ->poll('10s')
        ->columns([
            Tables\Columns\TextColumn::make('cliente')
                ->label('Cliente')
                ->getStateUsing(fn($record) => $record->client_name ?? $record->user?->name)
                ->description(fn($record) => $record->service?->name) // Mostra o serviço embaixo
                ->weight('bold') // Negrito para destaque
                ->searchable(),

            Tables\Columns\TextColumn::make('scheduled_at')
                ->label('Horário')
                ->dateTime('H:i') // Foco na HORA (o dia já está no filtro/cabeçalho geralmente)
                ->description(fn($record) => $record->scheduled_at->format('d/m/Y'))
                ->sortable(),

            Tables\Columns\TextColumn::make('barber.name')
                ->label('Barbeiro')
                ->icon('heroicon-m-user')
                ->color('gray')
                ->toggleable(),

            Tables\Columns\TextColumn::make('total_price')
                ->label('Valor')
                ->money('BRL')
                ->alignEnd(), // Alinhamento numérico correto

            // --- STATUS VISUAL E COLORIDO (O Pedido do Cliente) ---
            Tables\Columns\TextColumn::make('status')
                ->label('Situação')
                ->badge() // O formato "Pílula" colorido
                ->formatStateUsing(fn(string $state): string => match ($state) {
                    'pending'   => 'Pendente',
                    'confirmed' => 'Confirmado',
                    'completed' => 'Concluído',
                    'cancelled' => 'Cancelado',
                    'no_show'   => 'Não Compareceu',
                    default     => $state,
                })
                ->color(fn(string $state): string => match ($state) {
                    'pending'   => 'warning', // Amarelo
                    'confirmed' => 'info',    // Azul
                    'completed' => 'success', // Verde
                    'cancelled' => 'danger',  // Vermelho
                    'no_show'   => 'danger',  // Vermelho Escuro
                    default     => 'gray',
                })
                ->icon(fn(string $state): string => match ($state) {
                    'pending'   => 'heroicon-m-clock',
                    'confirmed' => 'heroicon-m-calendar-days',
                    'completed' => 'heroicon-m-check-badge',
                    'cancelled' => 'heroicon-m-x-circle',
                    'no_show'   => 'heroicon-m-user-minus',
                    default     => 'heroicon-m-question-mark-circle',
                })
                ->sortable(),
                
            // Indicador de Pagamento (Discreto, mas visível)
            Tables\Columns\IconColumn::make('payment_status')
                ->label('Pago?')
                ->icon(fn(string $state) => match ($state) {
                    'approved' => 'heroicon-s-currency-dollar',
                    default    => 'heroicon-o-currency-dollar',
                })
                ->color(fn(string $state) => match ($state) {
                    'approved' => 'success',
                    default    => 'gray',
                })
                ->tooltip(fn($record) => $record->payment_status === 'approved' ? 'Pago' : 'Pendente'),
        ])
        ->defaultSort('scheduled_at', 'desc')
        ->filters([
            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'pending'   => 'Pendente',
                    'confirmed' => 'Confirmado',
                    'completed' => 'Concluído',
                    'cancelled' => 'Cancelado',
                ]),
            Tables\Filters\Filter::make('data_agendamento')
                ->form([
                    DatePicker::make('data_inicial')->label('Data'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['data_inicial'], fn($query, $date) => $query->whereDate('scheduled_at', $date));
                }),
        ])
        ->actions([
            // === FLUXO DE AÇÕES INTELIGENTES ===
            
            // 1. CONFIRMAR (Resolve o problema de "mudar status")
            Tables\Actions\Action::make('confirm')
                ->label('Confirmar')
                ->icon('heroicon-o-hand-thumb-up')
                ->color('info') // Botão Azul
                ->button()      // Estilo botão cheio para chamar atenção
                ->visible(fn(Appointment $record) => $record->status === 'pending')
                ->action(fn(Appointment $record) => $record->update(['status' => 'confirmed']))
                ->successNotificationTitle('Agendamento Confirmado!'),

            // 2. FINALIZAR (Seu botão existente)
            Tables\Actions\Action::make('complete')
                ->label('Concluir')
                ->icon('heroicon-o-check')
                ->color('success') // Botão Verde
                ->button()
                ->visible(fn(Appointment $record) => $record->status === 'confirmed')
                ->requiresConfirmation()
                ->action(fn(Appointment $record) => $record->update(['status' => 'completed'])),

            // 3. PAGAR PIX (Se não pagou ainda)
            Tables\Actions\Action::make('pay_pix')
                ->label('Pix')
                ->icon('heroicon-o-qr-code')
                ->color('warning')
                ->iconButton()
                ->visible(fn(Appointment $record) => $record->payment_status !== 'approved' && $record->status !== 'cancelled' && $record->status !== 'completed')
                ->modalHeading('Receber via Pix')
                ->modalContent(function (Appointment $record, PaymentService $service) {
                    // (Sua lógica do Pix mantém igual)
                    if (empty($record->pix_copy_paste) || $record->payment_status === 'cancelled') {
                            $result = $service->createPixPayment($record);
                            if (! $result['success']) {
                                Notification::make()->title('Erro Pix')->body($result['error'])->danger()->send();
                                return view('filament.payments.error-modal', ['error' => $result['error']]);
                            }
                            $record->refresh();
                    }
                    return view('filament.payments.pix-modal', ['record' => $record]);
                })
                ->modalSubmitAction(false)
                ->modalCancelAction(fn($action) => $action->label('Fechar')),

            // MENU DE "MAIS OPÇÕES" (Para limpar a tela)
            Tables\Actions\ActionGroup::make([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('mark_no_show')
                    ->label('Cliente Faltou')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(Appointment $record) => !in_array($record->status, ['cancelled', 'no_show']))
                    ->action(fn(Appointment $record) => $record->update(['status' => 'no_show'])),
                    
                Tables\Actions\Action::make('cancel')
                    ->label('Cancelar Agendamento')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn(Appointment $record) => $record->update(['status' => 'cancelled'])),
            ]),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
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