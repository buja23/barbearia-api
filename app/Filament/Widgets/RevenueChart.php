<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Faturamento Anual';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $query = Appointment::query()
            ->where('status', 'completed')
            ->when(
                filament()->getTenant(),
                fn ($query, $tenant) => $query->where('barbershop_id', $tenant->id)
            );

        $data = Trend::query($query)
            ->between(
                start: now()->subYear(),
                end: now(),
            )
            ->perMonth()
            ->sum('total_price');

        return [
            'datasets' => [
                [
                    'label' => 'Receita (R$)',
                    'data' => $data->map(
                        fn (TrendValue $value) => $value->aggregate
                    ),
                    'fill' => 'start',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => '#3b82f6',
                    'tension' => 0.4,
                ],
            ],

            'labels' => $data->map(
                fn (TrendValue $value) =>
                    \Carbon\Carbon::parse($value->date)->format('M Y')
            ),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}