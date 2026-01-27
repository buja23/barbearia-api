<?php
namespace App\Filament\Widgets;

use App\Models\Appointment;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class CalendarWidget extends Widget
{
    protected static string $view = 'filament.widgets.calendar-widget';

    public $selectedDate;

    public static function canView(): bool
    {
        // Oculta no Dashboard principal, aparece só na página de recursos
        return ! request()->routeIs('filament.admin.pages.dashboard');
    }

// No CalendarWidget.php
    public function selectDate($date)
    {
        // Isso recarrega a página filtrando a tabela de baixo
        return redirect()->route('filament.admin.resources.appointments.index', [
            'tableFilters[data_agendamento][data_inicial]' => $date,
        ]);
    }

    public function getCalendarEvents(): array
    {
        return Cache::remember('calendar_heatmap_' . now()->format('Y-m-d-H'), 60, function () {
            $start = now()->startOfMonth()->subWeek();
            $end   = now()->endOfMonth()->addWeek();

            // Agrupa por dia e conta
            $appointments = Appointment::query()
                ->selectRaw('DATE(scheduled_at) as date, COUNT(*) as count')
                ->whereBetween('scheduled_at', [$start, $end])
                ->groupBy('date')
                ->get();

            return $appointments->map(function ($day) {
                // Regras de Lotação (Ajuste esses números conforme a realidade da barbearia)
                $count = $day->count;

                if ($count >= 15) {
                    $color = '#ef4444'; // Vermelho (Lotado)
                    $title = 'Lotado';
                } elseif ($count >= 8) {
                    $color = '#f97316'; // Laranja (Médio)
                    $title = 'Médio';
                } else {
                    $color = '#3b82f6'; // Azul (Tranquilo)
                    $title = 'Livre';
                }

                return [
                    'title'           => ' ', // Título vazio para não poluir
                    'start'           => $day->date,
                    'display'         => 'background', // Isso faz o evento ficar no fundo da célula
                    'backgroundColor' => $color,
                    'borderColor'     => 'transparent',
                    'extendedProps'   => [
                        'status' => $title,
                        'count'  => $count,
                    ],
                ];
            })->toArray();
        });
    }
}
