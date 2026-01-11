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
        $selectedDate = Carbon::parse($date);
        $service = Service::find($serviceId);
        
        // Se não tiver serviço selecionado, usamos 30 min como padrão para o grid
        $duration = $service ? $service->duration : 30; 

        $openingHour = OpeningHour::where('barbershop_id', $barber->barbershop_id)
            ->where('day_of_week', $selectedDate->dayOfWeek)
            ->first();

        if (!$openingHour || $openingHour->is_closed) return [];

        $start = $selectedDate->copy()->setTimeFromTimeString($openingHour->opening_time);
        $end = $selectedDate->copy()->setTimeFromTimeString($openingHour->closing_time);

        // Geramos slots a cada 15 ou 30 min para dar flexibilidade de início
        $period = CarbonPeriod::create($start, '15 minutes', $end->copy()->subMinutes($duration)); 

        $busyAppointments = Appointment::where('barber_id', $barber->id)
            ->whereDate('scheduled_at', $selectedDate)
            ->whereIn('status', ['confirmed', 'pending', 'completed'])
            ->get();

        $availableSlots = [];

        foreach ($period as $slot) {
            $slotStart = $slot->copy();
            $slotEnd = $slot->copy()->addMinutes($duration); // Onde o serviço terminaria

            // 1. Verificar se o BLOCO INTEIRO cabe dentro do horário de funcionamento
            if ($slotEnd->format('H:i:s') > $openingHour->closing_time) continue;

            // 2. Verificar se o BLOCO INTEIRO invade o almoço
            if ($barber->lunch_start && $barber->lunch_end) {
                if ($this->hasOverlap($slotStart, $slotEnd, $barber->lunch_start, $barber->lunch_end)) {
                    continue;
                }
            }

            // 3. Verificar se o BLOCO INTEIRO invade outro agendamento
            $conflict = false;
            foreach ($busyAppointments as $app) {
                // Aqui assumimos que cada agendamento existente também tem uma duração
                $appStart = $app->scheduled_at;
                $appEnd = $app->scheduled_at->copy()->addMinutes($app->service->duration ?? 30);

                if ($this->hasOverlap($slotStart, $slotEnd, $appStart->format('H:i:s'), $appEnd->format('H:i:s'))) {
                    $conflict = true;
                    break;
                }
            }

            if (!$conflict) {
                $availableSlots[] = $slot->format('H:i');
            }
        }

        return $availableSlots;
    }

    // Função auxiliar para detectar sobreposição de horários
    private function hasOverlap($start, $end, $busyStartStr, $busyEndStr): bool
    {
        $slotStart = $start->format('H:i:s');
        $slotEnd = $end->format('H:i:s');

        return ($slotStart < $busyEndStr && $slotEnd > $busyStartStr);
    }
}