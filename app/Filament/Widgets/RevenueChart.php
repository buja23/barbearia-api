<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Faturamento Mensal (R$)';

    protected function getData(): array
    {
        // Aqui pegamos o faturamento dos últimos 6 meses
        // Nota: Para usar a classe Trend, você precisaria instalar o pacote 'flowframe/laravel-trend'
        // Por agora, faremos uma contagem simples de faturamento por agendamento concluído
        
        return [
            'datasets' => [
                [
                    'label' => 'Faturamento',
                    'data' => [1500, 2200, 1800, 2500, 2100, 3000], // Exemplo estático para teste inicial
                    'borderColor' => '#10b981',
                ],
            ],
            'labels' => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}