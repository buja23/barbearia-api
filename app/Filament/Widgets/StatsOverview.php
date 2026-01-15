<?php
namespace App\Filament\Widgets;

use App\Models\Appointment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
{
    // Faturamento Real (O que já foi pago/concluído)
    $revenueReal = Appointment::where('status', 'completed')
        ->whereMonth('scheduled_at', now()->month)
        ->sum('total_price');

    // Faturamento Previsto (O que está agendado mas ainda não aconteceu)
    $revenuePredicted = Appointment::where('status', 'confirmed')
        ->whereMonth('scheduled_at', now()->month)
        ->sum('total_price');

    return [
        Stat::make('Faturamento Real (Mês)', 'R$ ' . number_format($revenueReal, 2, ',', '.'))
            ->description('Dinheiro em caixa (Concluídos)')
            ->descriptionIcon('heroicon-m-check-badge')
            ->color('success'),

        Stat::make('Previsão de Receita', 'R$ ' . number_format($revenuePredicted, 2, ',', '.'))
            ->description('Total em agendamentos futuros')
            ->descriptionIcon('heroicon-m-calendar-days')
            ->color('warning'),

        Stat::make('Assinantes Ativos', \App\Models\Subscription::where('status', 'active')->count())
            ->description('Receita recorrente garantida')
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->color('primary'),
    ];
}
}
