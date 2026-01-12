<?php

namespace App\Services;

use App\Models\Barber;
use App\Models\Appointment;
use App\Models\OpeningHour;
use App\Models\Service;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class BookingService
{
    public function getAvailableSlots(Barber $barber, string $date, int $serviceId = null): array
    {
        if (!$serviceId) return []; // Se não houver serviço, não há como calcular slots

        $selectedDate = Carbon::parse($date);
        $service = Service::find($serviceId);
        
        // Pega a duração diretamente da tabela de services (duration_minutes)
        if (!$service) return []; 
        $duration = $service->duration_minutes;

        $openingHour = OpeningHour::where('barbershop_id', $barber->barbershop_id)
            ->where('day_of_week', $selectedDate->dayOfWeek)
            ->first();

        if (!$openingHour || $openingHour->is_closed) return [];

        $start = $selectedDate->copy()->setTimeFromTimeString($openingHour->opening_time);
        $end = $selectedDate->copy()->setTimeFromTimeString($openingHour->closing_time);

        // O período de início vai da abertura até o horário de fecho MENOS a duração do serviço
        $period = CarbonPeriod::create($start, '15 minutes', $end->copy()->subMinutes($duration)); 

        // Carrega agendamentos existentes com a relação service
        $busyAppointments = Appointment::with('service')
            ->where('barber_id', $barber->id)
            ->whereDate('scheduled_at', $selectedDate)
            ->whereIn('status', ['confirmed', 'pending', 'completed'])
            ->get();

        $availableSlots = [];

        foreach ($period as $slot) {
            $slotStart = $slot->copy();
            $slotEnd = $slot->copy()->addMinutes($duration);

            $conflict = false;

            // 1. Verificar sobreposição com o Almoço do barbeiro
            if ($barber->lunch_start && $barber->lunch_end) {
                if ($this->hasOverlap($slotStart, $slotEnd, $barber->lunch_start, $barber->lunch_end)) {
                    $conflict = true;
                }
            }

            // 2. Verificar sobreposição com outros agendamentos
            if (!$conflict) {
                foreach ($busyAppointments as $app) {
                    $appStart = $app->scheduled_at;
                    // Usa a duração do agendamento que já está no banco
                    $appDuration = $app->service->duration_minutes ?? 30;
                    $appEnd = $appStart->copy()->addMinutes($appDuration);

                    if ($this->hasOverlap($slotStart, $slotEnd, $appStart->format('H:i:s'), $appEnd->format('H:i:s'))) {
                        $conflict = true;
                        break;
                    }
                }
            }

            if (!$conflict) {
                $availableSlots[] = $slot->format('H:i');
            }
        }

        return $availableSlots;
    }

    private function hasOverlap(Carbon $start, Carbon $end, string $busyStartStr, string $busyEndStr): bool
    {
        $slotStart = $start->format('H:i:s');
        $slotEnd = $end->format('H:i:s');

        // Um horário conflita se o início do novo é antes do fim do ocupado 
        // E o fim do novo é depois do início do ocupado.
        return ($slotStart < $busyEndStr && $slotEnd > $busyStartStr);
    }
}