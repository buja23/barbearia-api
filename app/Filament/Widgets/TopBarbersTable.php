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
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        return $table
            ->query(
                Barber::query()
                    ->when(
                        filament()->getTenant(),
                        fn ($query, $tenant) =>
                            $query->where('barbershop_id', $tenant->id)
                    )

                    ->withCount([
                        'appointments' => function (Builder $query) use (
                            $startOfMonth,
                            $endOfMonth
                        ) {
                            $query
                                ->where('status', 'completed')
                                ->whereBetween('scheduled_at', [
                                    $startOfMonth,
                                    $endOfMonth,
                                ]);
                        }
                    ])

                    ->withSum([
                        'appointments as total_revenue' => function (
                            Builder $query
                        ) use (
                            $startOfMonth,
                            $endOfMonth
                        ) {
                            $query
                                ->where('status', 'completed')
                                ->whereBetween('scheduled_at', [
                                    $startOfMonth,
                                    $endOfMonth,
                                ]);
                        }
                    ], 'total_price')

                    ->orderByDesc('total_revenue')
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

            ->paginated(false);
    }
}