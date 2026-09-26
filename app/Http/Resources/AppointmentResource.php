<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use App\Services\BookingService;
use App\Services\PaymentService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AppointmentResource extends Resource
{
    protected static ?string $model =
        Appointment::class;

    protected static ?string $navigationIcon =
        'heroicon-o-calendar-days';

    protected static ?string $navigationLabel =
        'Agendamentos';

    protected static ?string $modelLabel =
        'Agendamento';

    protected static ?string $pluralModelLabel =
        'Agendamentos';

    protected static ?string $tenantOwnershipRelationshipName =
        'barbershop';

    /*
    |--------------------------------------------------------------------------
    | Conta demo
    |--------------------------------------------------------------------------
    */

    private static function isDemoUser(): bool
    {
        return config('demo.enabled')
            && auth()->check()
            && auth()->user()->email === config('demo.email');
    }

    public static function form(
        Form $form
    ): Form {
        return $form
            ->schema([

                Section::make(
                    'Agendamento Inteligente'
                )
                    ->description(
                        'Selecione o barbeiro e a data para visualizar horários disponíveis.'
                    )

                    ->schema([

                        Select::make(
                            'barber_id'
                        )
                            ->relationship(
                                'barber',
                                'name'
                            )
                            ->required()
                            ->live()
                            ->label('Barbeiro'),

                        DatePicker::make(
                            'appointment_date'
                        )
                            ->label(
                                'Data do Corte'
                            )
                            ->required()
                            ->live()
                            ->native(false)
                            ->displayFormat(
                                'd/m/Y'
                            )
                            ->closeOnDateSelection()
                            ->dehydrated(false)
                            ->locale('pt_BR'),

                        Select::make(
                            'appointment_time'
                        )
                            ->label(
                                'Horários Livres'
                            )
                            ->required()

                            ->options(
                                function (
                                    Get $get,
                                    BookingService $service
                                ) {

                                    $barberId =
                                        $get(
                                            'barber_id'
                                        );

                                    $date =
                                        $get(
                                            'appointment_date'
                                        );

                                    $serviceId =
                                        $get(
                                            'service_id'
                                        );

                                    if (
                                        ! $barberId
                                        || ! $date
                                        || ! $serviceId
                                    ) {
                                        return [];
                                    }

                                    $barber =
                                        \App\Models\Barber::find(
                                            $barberId
                                        );

                                    return collect(
                                        $service
                                            ->getAvailableSlots(
                                                $barber,
                                                $date,
                                                $serviceId
                                            )
                                    )
                                        ->mapWithKeys(
                                            fn ($slot) => [
                                                $slot =>
                                                    $slot,
                                            ]
                                        )
                                        ->toArray();
                                }
                            )

                            ->live()
                            ->dehydrated(false)

                            ->afterStateUpdated(
                                function (
                                    $state,
                                    Get $get,
                                    Set $set
                                ) {

                                    $date =
                                        $get(
                                            'appointment_date'
                                        );

                                    if (
                                        $date
                                        && $state
                                    ) {

                                        $cleanDate =
                                            Carbon::parse(
                                                $date
                                            )
                                                ->format(
                                                    'Y-m-d'
                                                );

                                        $set(
                                            'scheduled_at',
                                            "{$cleanDate} {$state}:00"
                                        );
                                    }
                                }
                            ),

                        Hidden::make(
                            'scheduled_at'
                        )
                            ->required(),

                        Placeholder::make(
                            'scheduled_preview'
                        )
                            ->label(
                                'Horário Confirmado'
                            )

                            ->content(
                                fn (
                                    Get $get
                                ): string =>
                                    $get(
                                        'scheduled_at'
                                    )
                                        ? Carbon::parse(
                                            $get(
                                                'scheduled_at'
                                            )
                                        )
                                            ->format(
                                                'd/m/Y \à\s H:i'
                                            )
                                        : '— Selecione o barbeiro, data e horário acima —'
                            )

                            ->columnSpanFull(),

                    ])
                    ->columns(3),

                Section::make(
                    'Detalhes do Serviço'
                )
                    ->schema([

                        Select::make(
                            'service_id'
                        )
                            ->relationship(
                                'service',
                                'name'
                            )
                            ->required()
                            ->live()

                            ->afterStateUpdated(
                                function (
                                    $state,
                                    Set $set
                                ) {

                                    $service =
                                        \App\Models\Service::find(
                                            $state
                                        );

                                    if ($service) {

                                        $set(
                                            'total_price',
                                            $service->price
                                        );
                                    }
                                }
                            )

                            ->label('Serviço'),

                        TextInput::make(
                            'total_price'
                        )
                            ->numeric()
                            ->prefix('R$')
                            ->readOnly()
                            ->label(
                                'Valor do Serviço'
                            ),

                        Select::make('status')
                            ->label(
                                'Status do Agendamento'
                            )
                            ->options([
                                'pending' =>
                                    'Pendente',

                                'confirmed' =>
                                    'Confirmado',

                                'canceled' =>
                                    'Cancelado',

                                'completed' =>
                                    'Concluído',
                            ])
                            ->default(
                                'confirmed'
                            )
                            ->required(),

                    ])
                    ->columns(3),

                Section::make(
                    'Informações do Cliente'
                )
                    ->schema([

                        TextInput::make(
                            'client_name'
                        )
                            ->label(
                                'Nome do Cliente'
                            )
                            ->placeholder(
                                'Ex: João da Silva'
                            ),

                        TextInput::make(
                            'client_phone'
                        )
                            ->label(
                                'Telefone/WhatsApp'
                            )
                            ->mask(
                                '(99) 99999-9999'
                            )
                            ->tel(),

                    ])
                    ->columns(2),

            ]);
    }

    public static function table(
        Table $table
    ): Table {
        return $table

            /*
             * Removemos:
             *
             * ->poll('10s')
             *
             * para evitar chamadas constantes ao Render/Neon.
             */

            ->columns([

                Tables\Columns\TextColumn::make(
                    'cliente'
                )
                    ->label('Cliente')

                    ->getStateUsing(
                        fn ($record) =>
                            $record->client_name
                            ?? $record->user?->name
                    )

                    ->description(
                        fn ($record) =>
                            $record->service?->name
                    )

                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make(
                    'scheduled_at'
                )
                    ->label('Horário')
                    ->dateTime('H:i')

                    ->description(
                        fn ($record) =>
                            $record
                                ->scheduled_at
                                ->format(
                                    'd/m/Y'
                                )
                    )

                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'barber.name'
                )
                    ->label('Barbeiro')
                    ->icon(
                        'heroicon-m-user'
                    )
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make(
                    'total_price'
                )
                    ->label('Valor')
                    ->money('BRL')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make(
                    'status'
                )
                    ->label('Situação')
                    ->badge()

                    ->formatStateUsing(
                        fn (
                            string $state
                        ): string =>
                            match ($state) {

                                'pending' =>
                                    'Pendente',

                                'confirmed' =>
                                    'Confirmado',

                                'completed' =>
                                    'Concluído',

                                'canceled' =>
                                    'Cancelado',

                                'no_show' =>
                                    'Não Compareceu',

                                default =>
                                    $state,
                            }
                    )

                    ->color(
                        fn (
                            string $state
                        ): string =>
                            match ($state) {

                                'pending' =>
                                    'warning',

                                'confirmed' =>
                                    'info',

                                'completed' =>
                                    'success',

                                'canceled' =>
                                    'danger',

                                'no_show' =>
                                    'danger',

                                default =>
                                    'gray',
                            }
                    )

                    ->icon(
                        fn (
                            string $state
                        ): string =>
                            match ($state) {

                                'pending' =>
                                    'heroicon-m-clock',

                                'confirmed' =>
                                    'heroicon-m-calendar-days',

                                'completed' =>
                                    'heroicon-m-check-badge',

                                'canceled' =>
                                    'heroicon-m-x-circle',

                                'no_show' =>
                                    'heroicon-m-user-minus',

                                default =>
                                    'heroicon-m-question-mark-circle',
                            }
                    )

                    ->sortable(),

                Tables\Columns\IconColumn::make(
                    'payment_status'
                )
                    ->label('Pago?')

                    ->icon(
                        fn (
                            ?string $state
                        ) =>
                            match ($state) {

                                'approved' =>
                                    'heroicon-s-currency-dollar',

                                default =>
                                    'heroicon-o-currency-dollar',
                            }
                    )

                    ->color(
                        fn (
                            ?string $state
                        ) =>
                            match ($state) {

                                'approved' =>
                                    'success',

                                default =>
                                    'gray',
                            }
                    )

                    ->tooltip(
                        fn ($record) =>
                            $record->payment_status
                                === 'approved'
                                    ? 'Pago'
                                    : 'Pendente'
                    ),

            ])

            ->defaultSort(
                'scheduled_at',
                'desc'
            )

            ->filters([

                Tables\Filters\SelectFilter::make(
                    'status'
                )
                    ->options([
                        'pending' =>
                            'Pendente',

                        'confirmed' =>
                            'Confirmado',

                        'completed' =>
                            'Concluído',

                        'canceled' =>
                            'Cancelado',
                    ]),

                Tables\Filters\Filter::make(
                    'data_agendamento'
                )
                    ->form([

                        DatePicker::make(
                            'data_inicial'
                        )
                            ->label('Data'),

                    ])

                    ->query(
                        function (
                            Builder $query,
                            array $data
                        ): Builder {

                            return $query
                                ->when(
                                    $data[
                                        'data_inicial'
                                    ] ?? null,

                                    fn (
                                        $query,
                                        $date
                                    ) =>
                                        $query->whereDate(
                                            'scheduled_at',
                                            $date
                                        )
                                );
                        }
                    ),

            ])

            ->actions([

                /*
                |--------------------------------------------------------------------------
                | Confirmar agendamento
                |--------------------------------------------------------------------------
                */

                Tables\Actions\Action::make(
                    'confirm'
                )
                    ->label('Confirmar')
                    ->icon(
                        'heroicon-o-hand-thumb-up'
                    )
                    ->color('info')
                    ->button()

                    ->visible(
                        fn (
                            Appointment $record
                        ) =>
                            $record->status
                                === 'pending'
                    )

                    ->action(
                        fn (
                            Appointment $record
                        ) =>
                            $record->update([
                                'status' =>
                                    'confirmed',
                            ])
                    )

                    ->successNotificationTitle(
                        'Agendamento Confirmado!'
                    ),

                /*
                |--------------------------------------------------------------------------
                | Dinheiro
                |--------------------------------------------------------------------------
                */

                Tables\Actions\Action::make(
                    'pay_cash'
                )
                    ->label('Dinheiro')
                    ->icon(
                        'heroicon-o-banknotes'
                    )
                    ->color('success')
                    ->button()
                    ->requiresConfirmation()

                    ->modalHeading(
                        'Receber em Dinheiro'
                    )

                    ->modalDescription(
                        'Confirmar o recebimento do valor total em dinheiro?'
                    )

                    ->visible(
                        fn (
                            Appointment $record
                        ) =>
                            $record->payment_status
                                !== 'approved'
                            && $record->status
                                !== 'canceled'
                    )

                    ->action(
                        fn (
                            Appointment $record
                        ) =>
                            $record->update([
                                'payment_status' =>
                                    'approved',

                                'payment_method' =>
                                    'cash',
                            ])
                    )

                    ->after(
                        fn () =>
                            Notification::make()
                                ->title(
                                    'Pagamento em Dinheiro Confirmado'
                                )
                                ->success()
                                ->send()
                    ),

                /*
                |--------------------------------------------------------------------------
                | PIX
                |--------------------------------------------------------------------------
                */

                Tables\Actions\Action::make(
                    'pay_pix'
                )
                    ->label('PIX')
                    ->icon(
                        'heroicon-o-qr-code'
                    )
                    ->color('warning')
                    ->iconButton()

                    ->visible(
                        fn (
                            Appointment $record
                        ) =>
                            $record->payment_status
                                !== 'approved'
                            && $record->status
                                !== 'canceled'
                            && $record->status
                                !== 'completed'
                    )

                    ->modalHeading(
                        fn () =>
                            self::isDemoUser()
                                ? 'PIX — Ambiente Demonstrativo'
                                : 'Receber via PIX'
                    )

                    ->modalContent(
                        function (
                            Appointment $record,
                            PaymentService $service
                        ) {

                            /*
                             * Conta demo:
                             * nenhuma integração externa.
                             */
                            if (
                                self::isDemoUser()
                            ) {

                                return view(
                                    'filament.payments.demo-pix-modal',
                                    [
                                        'record' =>
                                            $record,
                                    ]
                                );
                            }

                            /*
                             * Conta real.
                             */
                            $barbershop =
                                $record
                                    ->barbershop;

                            if (
                                empty(
                                    $barbershop
                                        ?->pix_key
                                )
                            ) {

                                $error =
                                    'Sua barbearia ainda não tem uma chave PIX cadastrada. Acesse Configurações → Minha Barbearia → Recebimento via PIX e cadastre sua chave.';

                                Notification::make()
                                    ->title(
                                        'Chave PIX não configurada'
                                    )
                                    ->body(
                                        $error
                                    )
                                    ->warning()
                                    ->send();

                                return view(
                                    'filament.payments.error-modal',
                                    [
                                        'error' =>
                                            $error,
                                    ]
                                );
                            }

                            if (
                                empty(
                                    $record
                                        ->pix_copy_paste
                                )
                                || $record
                                    ->payment_method
                                    !== 'pix'
                                || ! str_contains(
                                    $record
                                        ->pix_copy_paste,
                                    '***'
                                )
                            ) {

                                $result =
                                    $service
                                        ->generateLocalPixPayment(
                                            $record
                                        );

                                if (
                                    ! $result[
                                        'success'
                                    ]
                                ) {

                                    Notification::make()
                                        ->title(
                                            'Erro PIX'
                                        )
                                        ->body(
                                            $result[
                                                'error'
                                            ]
                                        )
                                        ->danger()
                                        ->send();

                                    return view(
                                        'filament.payments.error-modal',
                                        [
                                            'error' =>
                                                $result[
                                                    'error'
                                                ],
                                        ]
                                    );
                                }

                                $record->refresh();
                            }

                            return view(
                                'filament.payments.pix-modal',
                                [
                                    'record' =>
                                        $record,
                                ]
                            );
                        }
                    )

                    ->modalSubmitAction(
                        false
                    )

                    ->modalCancelAction(
                        fn ($action) =>
                            $action->label(
                                'Fechar'
                            )
                    ),

                /*
                |--------------------------------------------------------------------------
                | Confirmar PIX
                |--------------------------------------------------------------------------
                */

                Tables\Actions\Action::make(
                    'confirm_pix'
                )
                    ->label(
                        'Confirmar PIX'
                    )
                    ->icon(
                        'heroicon-o-check-badge'
                    )
                    ->color('success')
                    ->iconButton()

                    ->visible(
                        fn (
                            Appointment $record
                        ) =>
                            ! self::isDemoUser()
                            && $record
                                ->payment_status
                                === 'pending'
                            && $record
                                ->payment_method
                                === 'pix'
                            && ! empty(
                                $record
                                    ->pix_copy_paste
                            )
                            && $record
                                ->status
                                !== 'canceled'
                    )

                    ->requiresConfirmation()

                    ->modalHeading(
                        'Confirmar Pagamento PIX'
                    )

                    ->modalDescription(
                        'O cliente realizou o pagamento? Confirme para registrar como pago.'
                    )

                    ->action(
                        fn (
                            Appointment $record
                        ) =>
                            $record->update([
                                'payment_status' =>
                                    'approved',
                            ])
                    ),

                /*
                |--------------------------------------------------------------------------
                | Concluir atendimento
                |--------------------------------------------------------------------------
                */

                Tables\Actions\Action::make(
                    'complete'
                )
                    ->label('Concluir')
                    ->icon(
                        'heroicon-o-check'
                    )
                    ->color('gray')
                    ->iconButton()

                    ->visible(
                        fn (
                            Appointment $record
                        ) =>
                            $record->status
                                === 'confirmed'
                    )

                    ->requiresConfirmation()

                    ->action(
                        fn (
                            Appointment $record
                        ) =>
                            $record->update([
                                'status' =>
                                    'completed',
                            ])
                    ),

                /*
                |--------------------------------------------------------------------------
                | Mais opções
                |--------------------------------------------------------------------------
                */

                Tables\Actions\ActionGroup::make([

                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make(
                        'mark_no_show'
                    )
                        ->label(
                            'Cliente Faltou'
                        )
                        ->icon(
                            'heroicon-o-x-circle'
                        )
                        ->color('danger')
                        ->requiresConfirmation()

                        ->visible(
                            fn (
                                Appointment $record
                            ) =>
                                ! in_array(
                                    $record->status,
                                    [
                                        'canceled',
                                        'no_show',
                                    ]
                                )
                        )

                        ->action(
                            fn (
                                Appointment $record
                            ) =>
                                $record->update([
                                    'status' =>
                                        'no_show',
                                ])
                        ),

                    Tables\Actions\Action::make(
                        'cancel'
                    )
                        ->label(
                            'Cancelar Agendamento'
                        )
                        ->icon(
                            'heroicon-o-x-mark'
                        )
                        ->color('danger')
                        ->requiresConfirmation()

                        ->visible(
                            fn (
                                Appointment $record
                            ) =>
                                $record->status
                                    !== 'canceled'
                        )

                        ->action(
                            fn (
                                Appointment $record
                            ) =>
                                $record->update([
                                    'status' =>
                                        'canceled',
                                ])
                        ),

                ]),

            ])

            ->bulkActions([

                /*
                 * Usuário demo não pode apagar tudo.
                 */
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(
                        fn () =>
                            ! self::isDemoUser()
                    ),

            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'barber',
                'service',
                'user',
                'barbershop',
            ]);
    }

    public static function getPages(): array
    {
        return [

            'index' =>
                Pages\ListAppointments::route(
                    '/'
                ),

            'create' =>
                Pages\CreateAppointment::route(
                    '/create'
                ),

            'edit' =>
                Pages\EditAppointment::route(
                    '/{record}/edit'
                ),

        ];
    }

    public static function getWidgets(): array
    {
        return [

            \App\Filament\Widgets\CalendarWidget::class,

        ];
    }
}