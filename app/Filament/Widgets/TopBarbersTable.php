<?php

namespace App\Filament\Widgets;

use App\Models\Barber;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopBarbersTable extends BaseWidget
{
    protected static ?string $heading = 'Top Barbeiros (Mês Atual)';
    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Barber::query()
                    ->withCount(['appointments' => function (Builder $query) {
                        $query->where('status', 'completed')
                              ->whereMonth('scheduled_at', now()->month);
                    }])
                    ->withSum(['appointments as total_revenue' => function (Builder $query) {
                        $query->where('status', 'completed')
                              ->whereMonth('scheduled_at', now()->month);
                    }], 'total_price')
                    ->orderByDesc('total_revenue') // Ordena por quem faturou mais
            )
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('')
                    ->circular(),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Barbeiro')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('appointments_count')
                    ->label('Cortes')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Faturamento')
                    ->money('BRL')
                    ->sortable()
                    ->color('success'),
            ])
            ->paginated(false); // Mostra só a lista direta
    }
}