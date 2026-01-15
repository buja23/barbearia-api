<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Barber;
use App\Models\Subscription; // Importação necessária para o Módulo 4
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Importação vital para DB::transaction
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function getAvailableSlots(Request $request, BookingService $service)
    {
        $request->validate([
            'barber_id'  => 'required|exists:barbers,id',
            'date'       => 'required|date_format:Y-m-d',
            'service_id' => 'required|exists:services,id',
        ]);

        $barber = Barber::find($request->barber_id);
        $slots = $service->getAvailableSlots($barber, $request->date, $request->service_id);

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

        $user = $request->user();
        $service = Service::findOrFail($data['service_id']);

        // 1. Verificação de conflito
        $exists = Appointment::where('barber_id', $data['barber_id'])
            ->where('scheduled_at', $data['scheduled_at'])
            ->whereIn('status', ['confirmed', 'pending'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Horário ocupado.'], 422);
        }

        // 2. Lógica de Assinatura (Módulo 4)
        // Se você ainda não criou a relação no User.php, o código abaixo dará erro.
        $subscription = $user->activeSubscription; 
        $isSubscriptionBooking = $subscription && $subscription->remaining_cuts > 0;
        $finalPrice = $isSubscriptionBooking ? 0.00 : $service->price;

        $start = Carbon::parse($data['scheduled_at']);
        $end = $start->copy()->addMinutes($service->duration_minutes);

        // 3. Gravação com Transação
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
                'notes'        => $isSubscriptionBooking ? 'Agendado via assinatura' : null,
            ]);

            if ($isSubscriptionBooking) {
                $subscription->decrement('remaining_cuts');
            }

            return response()->json([
                'message' => $isSubscriptionBooking ? 'Agendado via assinatura!' : 'Agendado com sucesso!',
                'appointment' => $appointment,
                'remaining_cuts' => $isSubscriptionBooking ? $subscription->remaining_cuts : null
            ], 201);
        });
    }
}