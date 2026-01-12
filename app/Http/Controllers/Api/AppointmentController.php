<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Services\BookingService;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    // O App envia ?barber_id=1&date=2026-01-20&service_id=2
    public function getAvailableSlots(Request $request, BookingService $service)
    {
        $request->validate([
            'barber_id'  => 'required|exists:barbers,id',
            'date'       => 'required|date_format:Y-m-d',
            'service_id' => 'required|exists:services,id',
        ]);

        $barber = Barber::find($request->barber_id);

        // Reutiliza a lógica do Módulo 2
        $slots = $service->getAvailableSlots(
            $barber,
            $request->date,
            $request->service_id
        );

        return response()->json($slots);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'barber_id'    => 'required|exists:barbers,id',
            'service_id'   => 'required|exists:services,id',
            'scheduled_at' => 'required|date_format:Y-m-d H:i:s',
            'client_phone' => 'required|string',
        ]);

        // 1. Busca os detalhes do serviço selecionado
        $service = \App\Models\Service::findOrFail($data['service_id']);

        // 2. Verificação de conflito (Race Condition)
        $exists = \App\Models\Appointment::where('barber_id', $data['barber_id'])
            ->where('scheduled_at', $data['scheduled_at'])
            ->whereIn('status', ['confirmed', 'pending'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Desculpe, este horário foi ocupado agora há pouco.'], 422);
        }

        // 3. Calcula o horário de término (Duração vem do banco)
        $start = \Carbon\Carbon::parse($data['scheduled_at']);
        $end   = $start->copy()->addMinutes($service->duration_minutes);

        // 4. Cria o agendamento completo
        $appointment = \App\Models\Appointment::create([
            'barber_id'    => $data['barber_id'],
            'service_id'   => $data['service_id'],
            'user_id'      => $request->user()->id,   // Automático via Token
            'client_name'  => $request->user()->name, // Automático via Token
            'client_phone' => $data['client_phone'],
            'scheduled_at' => $data['scheduled_at'],
            'end_at'       => $end,            // Calculado automaticamente
            'total_price'  => $service->price, // Pego do banco (Resolve o erro 500)
            'status'       => 'confirmed',
        ]);

        return response()->json([
            'message'     => 'Agendado com sucesso!',
            'appointment' => $appointment,
        ], 201);
    }
}
