<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarbershopResource;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\Product;
use App\Services\PaymentService;
use App\Support\DemoAccess;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrderResource extends Resource
{
    use \App\Filament\Concerns\ProtectsDemoRecords;

    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon =
        'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel =
        'Vendas & Caixa';

    protected static ?int $navigationSort = 2;

    protected static ?string $tenantOwnershipRelationshipName =
        'barbershop';

    public static function canEdit(Model $record): bool
    {
        return ! DemoAccess::protects($record) && parent::canEdit($record);
    }

    public static function form(
        Forms\Form $form
    ): Forms\Form {
        return $form
            ->schema([

                Forms\Components\Section::make(
                    'Informações da Venda'
                )
                    ->schema([

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pendente',
                                'approved' => 'Aprovado',
                                'cancelled' => 'Cancelado',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\TextInput::make(
                            'total_amount'
                        )
                            ->label('Valor Total (R$)')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated(false)
                            ->minValue(0.01)
                            ->step(0.01)
                            ->helperText(
                                'Calculado automaticamente com base nos itens da venda.'
                            ),

                        Forms\Components\TextInput::make(
                            'payment_id'
                        )
                            ->label(
                                'ID Externo do Pagamento'
                            )
                            ->readOnly()
                            ->hidden(),

                        Forms\Components\Textarea::make(
                            'qr_code_base64'
                        )
                            ->label('QR Code Base64')
                            ->rows(3)
                            ->hidden(),

                    ])
                    ->columns(2),

                Forms\Components\Section::make(
                    'Itens da Venda'
                )
                    ->schema([

                        Forms\Components\Repeater::make('items')
                            ->label('Produtos')
                            ->relationship('items')

                            ->disabled(
                                fn (?Order $record) =>
                                    $record?->inventory_applied_at
                                    !== null
                            )

                            ->schema([

                                Forms\Components\Select::make(
                                    'product_id'
                                )
                                    ->label('Produto')

                                    ->options(function () {

                                        return Product::query()
                                            ->when(
                                                filament()
                                                    ->getTenant(),

                                                fn (
                                                    $query,
                                                    $tenant
                                                ) =>
                                                    $query->where(
                                                        'barbershop_id',
                                                        $tenant->id
                                                    )
                                            )
                                            ->pluck(
                                                'name',
                                                'id'
                                            )
                                            ->toArray();
                                    })

                                    ->required()
                                    ->searchable()
                                    ->live()

                                    ->afterStateUpdated(
                                        function (
                                            Forms\Set $set,
                                            $state
                                        ) {

                                            $product =
                                                Product::query()
                                                    ->when(
                                                        filament()
                                                            ->getTenant(),

                                                        fn (
                                                            $query,
                                                            $tenant
                                                        ) =>
                                                            $query->where(
                                                                'barbershop_id',
                                                                $tenant->id
                                                            )
                                                    )
                                                    ->find($state);

                                            if ($product) {
                                                $set(
                                                    'unit_price',
                                                    $product
                                                        ->sale_price
                                                );

                                                $set(
                                                    'cost_price',
                                                    $product
                                                        ->cost_price
                                                );
                                            }
                                        }
                                    )

                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                                Forms\Components\TextInput::make(
                                    'quantity'
                                )
                                    ->label('Quantidade')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required()
                                    ->live(),

                                Forms\Components\TextInput::make(
                                    'unit_price'
                                )
                                    ->label(
                                        'Preço Unit. (R$)'
                                    )
                                    ->numeric()
                                    ->readOnly(),

                                Forms\Components\TextInput::make(
                                    'cost_price'
                                )
                                    ->label(
                                        'Custo Unit. (R$)'
                                    )
                                    ->numeric()
                                    ->readOnly(),

                            ])
                            ->columns(4)
                            ->collapsible()
                            ->addActionLabel(
                                'Adicionar Produto'
                            ),

                    ]),

            ]);
    }

    public static function table(
        Table $table
    ): Table {
        return $table

            ->columns([

                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'created_at'
                )
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'total_amount'
                )
                    ->label('Total')
                    ->money('BRL'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()

                    ->formatStateUsing(
                        fn (string $state) =>
                            match ($state) {

                                'approved' =>
                                    'Aprovado',

                                'pending' =>
                                    'Pendente',

                                'cancelled' =>
                                    'Cancelado',

                                default =>
                                    $state,
                            }
                    )

                    ->color(
                        fn (string $state): string =>
                            match ($state) {

                                'approved' =>
                                    'success',

                                'pending' =>
                                    'warning',

                                default =>
                                    'danger',
                            }
                    ),

            ])

            ->defaultSort(
                'created_at',
                'desc'
            )

            ->actions([

                /*
                |--------------------------------------------------------------------------
                | PIX
                |--------------------------------------------------------------------------
                */

                Tables\Actions\Action::make('pay_pix')
                    ->label('PIX')
                    ->icon('heroicon-o-qr-code')
                    ->color('warning')
                    ->iconButton()

                    ->visible(
                        fn (Order $record) =>
                            $record->status !== 'approved'
                            && $record->status !== 'cancelled'
                    )

                    ->modalHeading(
                        fn (Order $record) =>
                            DemoAccess::protects($record)
                                ? 'PIX — Ambiente Demonstrativo'
                                : 'Receber venda via PIX'
                    )

                    ->modalContent(
                        function (
                            Order $record
                        ) {

                            /*
                             * Conta demo:
                             * nunca chama Mercado Pago.
                             */
                            if (DemoAccess::protects($record)) {

                                return view(
                                    'filament.payments.demo-pix-modal',
                                    [
                                        'record' => $record,
                                    ]
                                );
                            }

                            /*
                             * Conta real.
                             */
                            if (
                                blank(
                                    $record->pix_copy_paste
                                )
                                || $record->status
                                    === 'cancelled'
                            ) {

                                $result =
                                    app(PaymentService::class)
                                        ->createOrderPix(
                                            $record
                                        );

                                if (
                                    ! $result['success']
                                ) {

                                    Notification::make()
                                        ->title(
                                            'Erro ao gerar PIX'
                                        )
                                        ->body(
                                            $result['error']
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

                                            'actionUrl' =>
                                                $record
                                                    ->barbershop
                                                    ? BarbershopResource::getUrl(
                                                        'edit',
                                                        [
                                                            'record' =>
                                                                $record
                                                                    ->barbershop,
                                                        ]
                                                    )
                                                    : null,

                                            'actionLabel' =>
                                                'Cadastrar PIX da barbearia',
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

                    ->modalSubmitAction(false)

                    ->modalCancelAction(
                        fn ($action) =>
                            $action->label(
                                'Fechar'
                            )
                    ),

                /*
                |--------------------------------------------------------------------------
                | Ver PIX
                |--------------------------------------------------------------------------
                */

                Tables\Actions\Action::make(
                    'view_pix'
                )
                    ->label('Ver PIX')
                    ->icon(
                        'heroicon-o-qr-code'
                    )

                    ->visible(
                        fn (Order $record) =>
                            ! DemoAccess::protects($record)
                            && $record->status
                                === 'pending'
                            && filled(
                                $record
                                    ->pix_copy_paste
                            )
                    )

                    ->modalHeading(
                        'Pagamento via PIX'
                    )

                    ->modalContent(
                        fn (Order $record) =>
                            view(
                                'filament.payments.pix-modal',
                                [
                                    'record' =>
                                        $record,
                                ]
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
                    ->label('Confirmar PIX')
                    ->icon(
                        'heroicon-o-check-badge'
                    )
                    ->color('success')
                    ->requiresConfirmation()

                    ->modalHeading(
                        'Confirmar recebimento via PIX'
                    )

                    ->modalDescription(
                        'Confirma que o cliente já pagou esta venda via PIX?'
                    )

                    ->visible(
                        fn (Order $record) =>
                            ! DemoAccess::protects($record)
                            && $record->status
                                === 'pending'
                            && filled(
                                $record
                                    ->pix_copy_paste
                            )
                    )

                    ->action(
                        function (
                            Order $record
                        ) {
                            DemoAccess::ensureAllowed($record);
                            $record->update([
                                'status' =>
                                    'approved',
                            ]);

                            $record
                                ->refresh()
                                ->applyInventory();
                        }
                    )

                    ->after(
                        fn () =>
                            Notification::make()
                                ->title(
                                    'Venda confirmada por PIX'
                                )
                                ->success()
                                ->send()
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
                    ->requiresConfirmation()

                    ->visible(
                        fn (Order $record) =>
                            $record->status
                                !== 'approved'
                            && $record->status
                                !== 'cancelled'
                    )

                    ->action(
                        function (
                            Order $record
                        ) {

                            DemoAccess::ensureAllowed($record);
                            $record->update([
                                'status' =>
                                    'approved',

                                'pix_copy_paste' =>
                                    null,

                                'qr_code_base64' =>
                                    null,
                            ]);

                            $record
                                ->refresh()
                                ->applyInventory();
                        }
                    )

                    ->after(
                        fn () =>
                            Notification::make()
                                ->title(
                                    'Venda em dinheiro confirmada'
                                )
                                ->success()
                                ->send()
                    ),

                /*
                |--------------------------------------------------------------------------
                | Editar
                |--------------------------------------------------------------------------
                */

                Tables\Actions\EditAction::make(),

                /*
                |--------------------------------------------------------------------------
                | Excluir
                |--------------------------------------------------------------------------
                |
                | Não mostramos para a conta pública demo.
                |
                */

                Tables\Actions\DeleteAction::make(),

            ]);
    }

    public static function getPages(): array
    {
        return [

            'index' =>
                Pages\ListOrders::route('/'),

            'create' =>
                Pages\CreateOrder::route(
                    '/criar'
                ),

            'edit' =>
                Pages\EditOrder::route(
                    '/{record}/editar'
                ),

        ];
    }
}
