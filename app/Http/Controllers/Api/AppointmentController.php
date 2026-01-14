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

        $user    = $request->user();
        $service = Service::findOrFail($data['service_id']);

        // 1. Verificação de conflito (Race Condition)
        $exists = Appointment::where('barber_id', $data['barber_id'])
            ->where('scheduled_at', $data['scheduled_at'])
            ->whereIn('status', ['confirmed', 'pending'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Horário ocupado.'], 422);
        }

        // 2. Lógica de Assinatura (Módulo 4)
        // Buscamos a assinatura ativa do usuário
        $subscription          = $user->activeSubscription;
        $isSubscriptionBooking = false;
        $finalPrice            = $service->price;

        if ($subscription && $subscription->remaining_cuts > 0) {
            $isSubscriptionBooking = true;
            $finalPrice            = 0.00; // O corte sai de graça porque já foi pago na mensalidade
        }

        // 3. Cálculo de término
        $start = Carbon::parse($data['scheduled_at']);
        $end   = $start->copy()->addMinutes($service->duration_minutes);

        // 4. Gravação com Transação para garantir integridade
        return DB::transaction(function () use ($data, $user, $service, $end, $finalPrice, $subscription, $isSubscriptionBooking) {

            $appointment = Appointment::create([
                'barber_id'    => $data['barber_id'],
                'service_id'   => $data['service_id'],
                'user_id'      => $user->id,
                'client_name'  => $user->name,
                'client_phone' => $data['client_phone'],
                'scheduled_at' => $data['scheduled_at'],
                'end_at'       => $end,
                'total_price'  => $finalPrice,
                'status'       => 'confirmed',
                'notes'        => $isSubscriptionBooking ? 'Corte via assinatura' : null,
            ]);

            // Se for via assinatura, debitamos 1 corte do saldo do cliente
            if ($isSubscriptionBooking) {
                $subscription->decrement('remaining_cuts');
            }

            return response()->json([
                'message'        => $isSubscriptionBooking ? 'Agendado via assinatura!' : 'Agendado com sucesso!',
                'appointment'    => $appointment,
                'remaining_cuts' => $isSubscriptionBooking ? $subscription->remaining_cuts : null,
            ], 201);
        });
    }
}
