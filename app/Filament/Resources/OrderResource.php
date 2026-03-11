<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Vendas & Caixa';
    protected static ?int $navigationSort = 2;

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
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01),

                        Forms\Components\TextInput::make('payment_id')
                            ->label('ID do Pagamento')
                            ->placeholder('Opcional - ID do Mercado Pago/Pix'),

                        Forms\Components\Textarea::make('pix_copy_paste')
                            ->label('Chave Pix (Copy & Paste)')
                            ->helperText('Cole o código Pix aqui se pagamento é via Pix')
                            ->rows(3),

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
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Produto')
                                    ->options(Product::all()->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                                        $product = Product::find($state);
                                        if ($product) {
                                            $set('unit_price', $product->sale_price);
                                            $set('cost_price', $product->cost_price);
                                        }
                                    }),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantidade')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),

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
                // Ação para ver o Pix novamente
                Tables\Actions\Action::make('view_pix')
                    ->label('Ver Pix')
                    ->icon('heroicon-o-qr-code')
                    ->visible(fn (Order $record) => $record->status === 'pending')
                    ->modalHeading('Pagamento Pix')
                    ->modalContent(fn (Order $record) => view('filament.payments.pix-modal', ['record' => $record])),
                
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