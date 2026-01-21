<?php
namespace App\Filament\Widgets;

use App\Models\Appointment;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CalendarWidget extends Widget
{
    protected static string $view = 'filament.widgets.calendar-widget';

    public $selectedDate;

    public static function canView(): bool
    {
        return ! request()->routeIs('filament.admin.pages.dashboard');
    }

    public function selectDate($date)
    {
        $this->selectedDate = $date;
        // Simulando um pequeno delay para você ver o Loading Lindo (Remova em produção se quiser)
        // usleep(300000);
        $this->dispatch('filtrar-data', date: $date);
    }

    public function getCalendarEvents(): array
    {
        // Cacheia a query por 5 minutos para performance instantânea
        return Cache::remember('calendar_events_' . now()->format('Y-m'), 300, function () {

            // Pega dados do mês atual e arredores
            $start = now()->startOfMonth()->subDays(15);
            $end   = now()->endOfMonth()->addDays(15);

            $counts = Appointment::select(
                DB::raw('DATE(scheduled_at) as date'),
                DB::raw('count(*) as total')
            )
                ->whereBetween('scheduled_at', [$start, $end])
                ->groupBy('date')
                ->get();

            // Capacidade (Exemplo: 15 cortes/dia)
            $lotado = 15;
            $medio  = 8;

            // No método getCalendarEvents:
            return $counts->map(function ($day) use ($lotado, $medio) {
                                           // Definindo as cores das bolas
                $class = 'bg-evento-azul'; // Padrão: Bola Azul Claro

                if ($day->total >= $lotado) {
                    $class = 'bg-evento-vermelho';
                } elseif ($day->total >= $medio) {
                    $class = 'bg-evento-laranja';
                }

                return [
                    'start'      => $day->date,
                    'display'    => 'background', // Isso cria a "Bola" atrás do número
                    'classNames' => [$class],
                ];
            })->toArray();
        });
    }
}
