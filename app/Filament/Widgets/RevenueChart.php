<?php
namespace App\Filament\Widgets;

use App\Models\Appointment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Faturamento Mensal (R$)';
    protected static ?int $sort       = 2; // Aparece abaixo dos stats

    protected function getData(): array
    {
        // Alteramos o filtro de 'confirmed' para 'completed'
        $data = Appointment::select(
            DB::raw('SUM(total_price) as total'),
            DB::raw("to_char(scheduled_at, 'MM') as month")
        )
            ->where('status', 'completed') // Apenas o que foi concluído
            ->whereYear('scheduled_at', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
        $values = array_map(fn($m) => $data[$m] ?? 0, $months);

        return [
            'datasets' => [
                [
                    'label'           => 'Faturamento Realizado',
                    'data'            => array_values($values),
                    'borderColor'     => '#10b981',
                    'fill'            => 'start',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
            ],
            'labels'   => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
