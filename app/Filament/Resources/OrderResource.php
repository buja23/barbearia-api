<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
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
            ]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            // 'edit' => ... podemos remover edit se quiser, ou deixar para ver detalhes
        ];
    }
}