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
        $this->dispatch('filtrar-data', date: $date);
    }

    // 🚀 Gera as bolinhas coloridas baseadas na lotação
    public function getCalendarEvents(): array
    {
        // Chave única baseada no mês atual para o cache
        $cacheKey = 'calendar_events_' . now()->format('Y_m');

        // 🚀 SENIOR MOVE: Cache por 10 minutos.
        // Só recalculamos se o cache expirar.
        return Cache::remember($cacheKey, 600, function () {

            // Aqui vai a sua query pesada original...
            $counts = Appointment::select(
                DB::raw('DATE(scheduled_at) as date'),
                DB::raw('count(*) as total')
            )
            // Dica: Pegue um intervalo maior para cobrir viradas de mês visualmente
                ->whereBetween('scheduled_at', [now()->subMonth(), now()->addMonths(2)])
                ->groupBy('date')
                ->get();

            return $counts->map(function ($day) {
                $class = 'bg-evento-azul';
                if ($day->total >= 12) {
                    $class = 'bg-evento-vermelho';
                } elseif ($day->total >= 7) {
                    $class = 'bg-evento-laranja';
                }

                return [
                    'start'      => $day->date,
                    'display'    => 'background',
                    'classNames' => [$class],
                    // Adicione isso:
                    'title'      => "{$day->total} cortes agendados",
                ];
            })->toArray();
        });
    }
}
