<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class CalendarWidget extends Widget
{
    protected static string $view = 'filament.widgets.calendar-widget';

    public $selectedDate;

    public static function canView(): bool
    {
        // Oculta no Dashboard principal, aparece so na pagina de recursos
        return ! request()->routeIs('filament.admin.pages.dashboard');
    }

    public function selectDate($date)
    {
        $this->selectedDate = $date;
        $this->dispatch('filtrar-data', date: $date);
    }

    public function getCalendarEvents(): array
    {
        $tenantId = filament()->getTenant()?->id;

        // Cacheia por 5 minutos -- chave inclui tenant para isolamento correto
        return Cache::remember('calendar_events_' . auth()->id() . '_' . ($tenantId ?? 0), 300, function () use ($tenantId) {

            $start = now()->startOfMonth()->subDays(15);
            $end   = now()->endOfMonth()->addDays(15);

            // So conta pending + confirmed -- os mesmos que aparecem na aba padrao da tabela
            $appointments = Appointment::query()
                ->whereBetween('scheduled_at', [$start, $end])
                ->whereIn('status', ['pending', 'confirmed'])
                ->when($tenantId, fn ($q) => $q->where('barbershop_id', $tenantId))
                ->get();

            $grouped = $appointments->groupBy(function ($appointment) {
                return Carbon::parse($appointment->scheduled_at)->format('Y-m-d');
            });

            $lotado = 15;
            $medio  = 8;

            return $grouped->map(function ($dayAppointments, $dateString) use ($lotado, $medio) {
                $total = $dayAppointments->count();
                $class = 'bg-evento-azul';
                if ($total >= $lotado)    $class = 'bg-evento-vermelho';
                elseif ($total >= $medio) $class = 'bg-evento-laranja';

                return [
                    'start'      => $dateString,
                    'display'    => 'background',
                    'classNames' => [$class],
                    'allDay'     => true,
                ];
            })->values()->toArray();
        });
    }

    /**
     * Chamado pelo JS ao navegar de mes.
     * Busca eventos do intervalo e dispara evento Livewire para o calendario atualizar.
     */
    public function loadEventsForRange(string $start, string $end): void
    {
        $startDate = Carbon::parse($start)->startOfDay();
        $endDate   = Carbon::parse($end)->endOfDay();

        $tenantId = filament()->getTenant()?->id;

        // So conta pending + confirmed -- os mesmos que aparecem na aba padrao da tabela
        $appointments = Appointment::query()
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->whereIn('status', ['pending', 'confirmed'])
            ->when($tenantId, fn ($q) => $q->where('barbershop_id', $tenantId))
            ->get();

        $grouped = $appointments->groupBy(function ($appointment) {
            return Carbon::parse($appointment->scheduled_at)->format('Y-m-d');
        });

        $lotado = 15;
        $medio  = 8;

        $events = $grouped->map(function ($dayAppointments, $dateString) use ($lotado, $medio) {
            $total = $dayAppointments->count();
            $class = 'bg-evento-azul';
            if ($total >= $lotado)    $class = 'bg-evento-vermelho';
            elseif ($total >= $medio) $class = 'bg-evento-laranja';

            return [
                'start'      => $dateString,
                'display'    => 'background',
                'classNames' => [$class],
                'allDay'     => true,
            ];
        })->values()->toArray();

        $this->dispatch('calendar-events-loaded', events: $events);
    }
}