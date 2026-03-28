<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\BarbershopResource;
use App\Models\Order;
use App\Models\Product;
use App\Services\PaymentService;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Vendas & Caixa';
    protected static ?int $navigationSort = 2;
    // Scoping automático: só mostra pedidos da barbearia logada
    protected static ?string $tenantOwnershipRelationshipName = 'barbershop';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações da Venda')
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

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Valor Total (R$)')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated(false)
                            ->minValue(0.01)
                            ->step(0.01)
                            ->helperText('Calculado automaticamente com base nos itens da venda.'),

                        Forms\Components\TextInput::make('payment_id')
                            ->label('ID Externo do Pagamento')
                            ->readOnly()
                            ->hidden(),

                        Forms\Components\Textarea::make('qr_code_base64')
                            ->label('QR Code Base64')
                            ->rows(3)
                            ->hidden(),
                    ])->columns(2),

                Forms\Components\Section::make('Itens da Venda')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('Produtos')
                            ->relationship('items')
                            ->disabled(fn (?Order $record) => $record?->inventory_applied_at !== null)
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Produto')
                                    ->options(function () {
                                        return \App\Models\Product::query()
                                            ->when(
                                                filament()->getTenant(),
                                                fn ($q, $t) => $q->where('barbershop_id', $t->id)
                                            )
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    })
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                                        $product = Product::query()
                                            ->when(
                                                filament()->getTenant(),
                                                fn ($q, $t) => $q->where('barbershop_id', $t->id)
                                            )
                                            ->find($state);

                                        if ($product) {
                                            $set('unit_price', $product->sale_price);
                                            $set('cost_price', $product->cost_price);
                                        }
                                    })
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantidade')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required()
                                    ->live(),

                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Preço Unit. (R$)')
                                    ->numeric()
                                    ->readOnly(),

                                Forms\Components\TextInput::make('cost_price')
                                    ->label('Custo Unit. (R$)')
                                    ->numeric()
                                    ->readOnly(),
                            ])->columns(4)
                            ->collapsible()
                            ->addActionLabel('Adicionar Produto'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Data')->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('total_amount')->label('Total')->money('BRL'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        default => 'danger',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('pay_pix')
                    ->label('Pix')
                    ->icon('heroicon-o-qr-code')
                    ->color('warning')
                    ->iconButton()
                    ->visible(fn (Order $record) => $record->status !== 'approved' && $record->status !== 'cancelled')
                    ->modalHeading('Receber venda via PIX')
                    ->modalContent(function (Order $record, PaymentService $service) {
                        if (blank($record->pix_copy_paste) || $record->status === 'cancelled') {
                            $result = $service->createOrderPix($record);

                            if (! $result['success']) {
                                Notification::make()->title('Erro ao gerar PIX')->body($result['error'])->danger()->send();

                                return view('filament.payments.error-modal', [
                                    'error' => $result['error'],
                                    'actionUrl' => $record->barbershop
                                        ? BarbershopResource::getUrl('edit', ['record' => $record->barbershop])
                                        : null,
                                    'actionLabel' => 'Cadastrar PIX da barbearia',
                                ]);
                            }

                            $record->refresh();
                        }

                        return view('filament.payments.pix-modal', ['record' => $record]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn ($action) => $action->label('Fechar')),

                // Ação para ver o Pix novamente
                Tables\Actions\Action::make('view_pix')
                    ->label('Ver PIX')
                    ->icon('heroicon-o-qr-code')
                    ->visible(fn (Order $record) => $record->status === 'pending' && filled($record->pix_copy_paste))
                    ->modalHeading('Pagamento via PIX')
                    ->modalContent(fn (Order $record) => view('filament.payments.pix-modal', ['record' => $record])),

                Tables\Actions\Action::make('confirm_pix')
                    ->label('Confirmar PIX')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar recebimento via PIX')
                    ->modalDescription('Confirma que o cliente já pagou esta venda via PIX?')
                    ->visible(fn (Order $record) => $record->status === 'pending' && filled($record->pix_copy_paste))
                    ->action(function (Order $record) {
                        $record->update(['status' => 'approved']);
                        $record->refresh()->applyInventory();
                    })
                    ->after(fn () => Notification::make()->title('Venda confirmada por PIX')->success()->send()),

                Tables\Actions\Action::make('pay_cash')
                    ->label('Dinheiro')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record) => $record->status !== 'approved' && $record->status !== 'cancelled')
                    ->action(function (Order $record) {
                        $record->update([
                            'status' => 'approved',
                            'pix_copy_paste' => null,
                            'qr_code_base64' => null,
                        ]);
                        $record->refresh()->applyInventory();
                    })
                    ->after(fn () => Notification::make()->title('Venda em dinheiro confirmada')->success()->send()),
                
                // Ação para editar
                Tables\Actions\EditAction::make(),
                
                // Ação para deletar
                Tables\Actions\DeleteAction::make(),
            ]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/criar'),
            'edit' => Pages\EditOrder::route('/{record}/editar'),
        ];
    }
}