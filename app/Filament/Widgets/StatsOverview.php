<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\Subscription;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total de Clientes', User::where('role', 'client')->count())
                ->description('Clientes cadastrados no app')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Agendamentos (Mês)', Appointment::whereMonth('scheduled_at', now()->month)->count())
                ->description('Cortes marcados este mês')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),

            Stat::make('Assinantes Ativos', Subscription::where('status', 'active')->count())
                ->description('Receita recorrente garantida')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('primary'),
        ];
    }
}