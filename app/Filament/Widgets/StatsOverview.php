<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Define datas para comparação (Mês Atual vs Mês Passado)
        $now = Carbon::now();
        $startThisMonth = $now->copy()->startOfMonth();
        $startLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endLastMonth   = $now->copy()->subMonth()->endOfMonth();

        // --- LÓGICA DE FATURAMENTO (CORTES + PRODUTOS) ---
        
        // Faturamento Atual
        $receitaCortesAtual = Appointment::where('status', 'completed')
            ->where('scheduled_at', '>=', $startThisMonth)
            ->sum('total_price');
            
        $receitaProdutosAtual = Order::where('status', 'paid')
            ->where('created_at', '>=', $startThisMonth)
            ->sum('total_amount');
            
        $totalAtual = $receitaCortesAtual + $receitaProdutosAtual;

        // Faturamento Mês Passado (Para comparação)
        $receitaCortesPassado = Appointment::where('status', 'completed')
            ->whereBetween('scheduled_at', [$startLastMonth, $endLastMonth])
            ->sum('total_price');
            
        $receitaProdutosPassado = Order::where('status', 'paid')
            ->whereBetween('created_at', [$startLastMonth, $endLastMonth])
            ->sum('total_amount');
            
        $totalPassado = $receitaCortesPassado + $receitaProdutosPassado;

        // Cálculo da Tendência
        $diferenca = $totalAtual - $totalPassado;
        $icon = $diferenca >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        $color = $diferenca >= 0 ? 'success' : 'danger';
        $desc = $diferenca >= 0 ? 'Aumento em relação ao mês passado' : 'Queda em relação ao mês passado';

        // --- OUTRAS MÉTRICAS ---
        $totalAgendamentos = Appointment::where('scheduled_at', '>=', $startThisMonth)->count();
        $novosClientes = \App\Models\User::where('created_at', '>=', $startThisMonth)->count();

        return [
            Stat::make('Faturamento (Mês Atual)', Number::currency($totalAtual, 'BRL'))
                ->description($desc)
                ->descriptionIcon($icon)
                ->chart($diferenca >= 0 ? [2, 5, 8, 10] : [10, 8, 5, 2]) // Gráfico decorativo
                ->color($color),

            Stat::make('Agendamentos', $totalAgendamentos)
                ->description('Total este mês')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('Novos Clientes', $novosClientes)
                ->description('Cadastrados este mês')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),
        ];
    }
}