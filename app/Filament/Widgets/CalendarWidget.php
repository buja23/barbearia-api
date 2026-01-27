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
        // Oculta no Dashboard principal, aparece só na página de recursos
        return ! request()->routeIs('filament.admin.pages.dashboard');
    }

    public function selectDate($date)
    {
        $this->selectedDate = $date;
        $this->dispatch('filtrar-data', date: $date);
    }

    public function getCalendarEvents(): array
    {
        // Cacheia por 5 minutos para o calendário carregar instantaneamente
        return Cache::remember('calendar_events_' . auth()->id(), 300, function () {
            
            // 1. Pega dados do mês atual +- 15 dias de margem
            $start = now()->startOfMonth()->subDays(15);
            $end   = now()->endOfMonth()->addDays(15);

            $appointments = Appointment::query()
                ->whereBetween('scheduled_at', [$start, $end])
                ->where('status', '!=', 'cancelled')
                ->get();

            // 2. Agrupa pelo dia (Y-m-d) usando PHP (Funciona no Postgres e MySQL)
            $grouped = $appointments->groupBy(function ($appointment) {
                return Carbon::parse($appointment->scheduled_at)->format('Y-m-d');
            });

            // Configuração de Lotação
            $lotado = 15;
            $medio  = 8;

            // 3. Transforma no formato do FullCalendar
            return $grouped->map(function ($dayAppointments, $dateString) use ($lotado, $medio) {
                
                $total = $dayAppointments->count();
                
                // Define a cor baseada na lotação
                $class = 'bg-evento-azul'; // Padrão: Tranquilo

                if ($total >= $lotado) {
                    $class = 'bg-evento-vermelho'; // Lotado
                } elseif ($total >= $medio) {
                    $class = 'bg-evento-laranja'; // Médio
                }

                return [
                    'start'      => $dateString,
                    'display'    => 'background', // Cria a "Bola" de fundo
                    'classNames' => [$class],     // Aplica a nossa classe CSS
                    'allDay'     => true,
                ];
            })->values()->toArray(); 
        });
    }
}