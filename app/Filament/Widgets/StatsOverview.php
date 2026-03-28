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
        $now            = Carbon::now();
        $startThisMonth = $now->copy()->startOfMonth();
        $startLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endLastMonth   = $now->copy()->subMonth()->endOfMonth();
        $tenant         = filament()->getTenant();

        // Faturamento Atual (scoped por barbearia)
        $receitaCortesAtual = Appointment::where('status', 'completed')
            ->where('scheduled_at', '>=', $startThisMonth)
            ->when($tenant, fn ($q, $t) => $q->where('barbershop_id', $t->id))
            ->sum('total_price');

        $receitaProdutosAtual = Order::where('status', 'approved')
            ->where('created_at', '>=', $startThisMonth)
            ->when($tenant, fn ($q, $t) => $q->where('barbershop_id', $t->id))
            ->sum('total_amount');

        $totalAtual = $receitaCortesAtual + $receitaProdutosAtual;

        // Faturamento Mês Passado
        $receitaCortesPassado = Appointment::where('status', 'completed')
            ->whereBetween('scheduled_at', [$startLastMonth, $endLastMonth])
            ->when($tenant, fn ($q, $t) => $q->where('barbershop_id', $t->id))
            ->sum('total_price');

        $receitaProdutosPassado = Order::where('status', 'approved')
            ->whereBetween('created_at', [$startLastMonth, $endLastMonth])
            ->when($tenant, fn ($q, $t) => $q->where('barbershop_id', $t->id))
            ->sum('total_amount');

        $totalPassado = $receitaCortesPassado + $receitaProdutosPassado;

        $diferenca = $totalAtual - $totalPassado;
        $icon  = $diferenca >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        $color = $diferenca >= 0 ? 'success' : 'danger';
        $desc  = $diferenca >= 0 ? 'Aumento em relação ao mês passado' : 'Queda em relação ao mês passado';

        $totalAgendamentos = Appointment::where('scheduled_at', '>=', $startThisMonth)
            ->when($tenant, fn ($q, $t) => $q->where('barbershop_id', $t->id))
            ->count();

        // Clientes únicos com agendamentos este mês (nesta barbearia)
        $clientesAtendidos = Appointment::where('scheduled_at', '>=', $startThisMonth)
            ->when($tenant, fn ($q, $t) => $q->where('barbershop_id', $t->id))
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        return [
            Stat::make('Faturamento (Mês Atual)', Number::currency($totalAtual, 'BRL'))
                ->description($desc)
                ->descriptionIcon($icon)
                ->chart($diferenca >= 0 ? [2, 5, 8, 10] : [10, 8, 5, 2])
                ->color($color),

            Stat::make('Agendamentos', $totalAgendamentos)
                ->description('Total este mês')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('Clientes Atendidos', $clientesAtendidos)
                ->description('Clientes únicos este mês')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),
        ];
    }
}