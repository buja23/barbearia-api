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
    protected int | string | array $columnSpan = 'full'; // Ocupa a largura toda

    protected function getData(): array
    {
        // Busca dados dos últimos 12 meses agrupados por mês
        $data = Trend::model(Appointment::class)
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
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    'fill' => 'start', // Cria o efeito de área preenchida
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)', // Azul transparente
                    'borderColor' => '#3b82f6', // Azul sólido
                    'tension' => 0.4, // Curva suave
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => \Carbon\Carbon::parse($value->date)->format('M Y')),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}