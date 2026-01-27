<?php
namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\PaymentService;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model           = Product::class;
    protected static ?string $navigationIcon  = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Estoque de Produtos';
    protected static ?string $modelLabel      = 'Produto';
    protected static ?int $navigationSort     = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Dados do Produto')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome do Produto')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'usage'  => 'Uso Interno (Material)',
                                'resale' => 'Revenda (Cliente)',
                            ])
                            ->required()
                            ->default('usage'),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Financeiro e Estoque')
                    ->schema([
                        Forms\Components\TextInput::make('cost_price')
                            ->label('Preço de Custo')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),

                        Forms\Components\TextInput::make('sale_price')
                            ->label('Preço de Venda')
                            ->numeric()
                            ->prefix('R$')
                            ->helperText('Deixe 0 se for apenas para uso interno'),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantidade em Estoque')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Forms\Components\TextInput::make('min_stock_alert')
                            ->label('Alerta de Mínimo')
                            ->numeric()
                            ->default(5)
                            ->helperText('Avisar quando chegar nessa quantidade'),
                    ])->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Produto')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'usage'  => 'Uso Interno',
                        'resale' => 'Revenda',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'usage'  => 'gray',
                        'resale' => 'info',
                    }),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Estoque')
                    ->sortable()
                    ->badge()
                    ->color(fn(Product $record): string => $record->isLowStock() ? 'danger' : 'success')
                    ->icon(fn(Product $record): string => $record->isLowStock() ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check'),

                Tables\Columns\TextColumn::make('cost_price')
                    ->label('Custo')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sale_price')
                    ->label('Venda')
                    ->money('BRL')
                    ->sortable(),
            ])
            ->defaultSort('quantity', 'asc') // Mostra os que estão acabando primeiro
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'usage'  => 'Uso Interno',
                        'resale' => 'Revenda',
                    ]),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Estoque Baixo')
                    ->query(fn($query) => $query->whereColumn('quantity', '<=', 'min_stock_alert')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // --- AÇÃO DE BAIXA INTELIGENTE ---
                Tables\Actions\Action::make('register_output')
                    ->label(fn(Product $record) => $record->type === 'resale' ? 'Vender' : 'Baixar')
                    // ... (ícones e cores mantidos) ...
                    ->form([
                        Forms\Components\TextInput::make('quantity_out')
                            ->label('Quantidade')
                            ->numeric()->default(1)->minValue(1)->required(),
                    ])
                    ->action(function (Product $record, array $data, PaymentService $paymentService) { // Injetamos o PaymentService
                        $qtd = (int) $data['quantity_out'];

                        if ($record->quantity < $qtd) {
                            Notification::make()->title('Estoque Insuficiente!')->danger()->send();
                            return;
                        }

                        // Se for REVENDA: Cria Pedido e Gera Pix
                        if ($record->type === 'resale') {

                            // 1. Cria o Pedido
                            $total = $qtd * $record->sale_price;
                            $order = Order::create([
                                'total_amount' => $total,
                                'status'       => 'pending',
                            ]);

                            // 2. Adiciona o Item
                            OrderItem::create([
                                'order_id'   => $order->id,
                                'product_id' => $record->id,
                                'quantity'   => $qtd,
                                'unit_price' => $record->sale_price,
                                'cost_price' => $record->cost_price,
                            ]);

                            // 3. Baixa o Estoque
                            $record->decrement('quantity', $qtd);

                            // 4. Gera o Pix
                            $paymentService->createOrderPix($order);

                            // 5. Notifica com Botão para ver o Pix
                            Notification::make()
                                ->title('Venda Iniciada!')
                                ->body("Pix gerado no valor de R$ " . number_format($total, 2, ',', '.'))
                                ->success()
                                ->persistent() // A notificação fica na tela atéOclicar
                                ->actions([
                                    NotificationAction::make('pay')
                                        ->label('Ver QR Code Pix')
                                        ->button()
                                        ->url("/admin/orders/{$order->id}/edit", shouldOpenInNewTab: true), // Vamos criar essa rota jaja
                                ])
                                ->send();

                        } else {
                            // Se for USO INTERNO: Só baixa
                            $record->decrement('quantity', $qtd);
                            Notification::make()->title('Material Baixado')->success()->send();
                        }
                    }),

                // Ação Rápida: Adicionar/Remover Estoque sem abrir formulário
                Tables\Actions\Action::make('update_stock')
                    ->label('Ajustar')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Nova Quantidade')
                            ->numeric()
                            ->required(),
                    ])
                    ->action(function (Product $record, array $data) {
                        $record->update(['quantity' => $data['quantity']]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
