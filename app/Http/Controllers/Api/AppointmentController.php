<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Service;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    /**
     * Busca horários livres para agendamento.
     * Usa BookingService para respeitar horários de funcionamento e intervalo de almoço.
     */
    public function getAvailableSlots(Request $request, $slug = null)
    {
        $request->validate([
            'date'       => 'required|date_format:Y-m-d',
            'barber_id'  => 'required|integer|exists:barbers,id',
            'service_id' => 'required|integer|exists:services,id',
        ]);

        $barber = Barber::findOrFail($request->query('barber_id'));
        $slots  = (new BookingService())->getAvailableSlots(
            $barber,
            $request->query('date'),
            (int) $request->query('service_id')
        );

        return response()->json($slots);
    }

    /**
     * Lista os agendamentos.
     * CORREÇÃO: Usa 'barber.barbershop' para evitar erro 500 se não houver coluna barbershop_id direta.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $appointments = Appointment::with(['barber.barbershop', 'service']) // <--- O PULO DO GATO
            ->where('user_id', $user->id)
            ->orderBy('scheduled_at', 'desc')
            ->get();

        return response()->json($appointments);
    }

    /**
     * Cria agendamento com validações robustas.
     */
    public function store(StoreAppointmentRequest $request)
    {
        $data = $request->validated();

        $user    = $request->user();
        $barber  = Barber::findOrFail($data['barber_id']);
        $service = Service::findOrFail($data['service_id']);

        // Verifica assinatura
        $subscription = $user->activeSubscription;
        $plan         = $subscription ? $subscription->plan : null;
        $limit        = $plan ? ($plan->cuts_per_month ?? 999) : 0;
        $hasBalance   = $subscription && ($subscription->uses_this_month < $limit);

        $finalPrice = $service->price;
        $notes      = null;
        $message    = 'Agendamento confirmado!';

        if ($subscription && $hasBalance) {
            $finalPrice = 0.00;
            $message    = 'Agendado via assinatura!';
            $notes      = 'Pago pelo plano ' . $plan->name;
        } elseif ($subscription && !$hasBalance) {
            $message = 'Limite do plano atingido. Cobrança avulsa gerada.';
            $notes   = 'Excedente do plano';
        }

        $start    = Carbon::parse($data['scheduled_at']);
        $duration = $service->duration_minutes ?? 30;
        $end      = $start->copy()->addMinutes($duration);

        return DB::transaction(function () use ($data, $user, $barber, $service, $start, $end, $finalPrice, $subscription, $hasBalance, $message, $notes) {

            $appointment = Appointment::create([
                'user_id'       => $user->id,
                'barber_id'     => $barber->id,
                'service_id'    => $service->id,
                'barbershop_id' => $barber->barbershop_id,
                'client_name'   => $user->name,
                'client_phone'  => $data['client_phone'] ?? $user->phone ?? 'Não informado',
                'scheduled_at'  => $start,
                'end_at'        => $end,
                'total_price'   => $finalPrice,
                'status'        => 'confirmed',
                'notes'         => $notes,
            ]);

            if ($subscription && $hasBalance) {
                $subscription->increment('uses_this_month');
            }

            return response()->json([
                'message'     => $message,
                'appointment' => $appointment,
            ], 201);
        });
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        $appointment = Appointment::find($id);

        // ✅ AUTORIZAÇÃO: Verificar se o agendamento pertence ao usuário
        if (!$appointment) {
            \Log::warning('Tentativa de deletar agendamento inexistente', [
                'appointment_id' => $id,
                'user_id' => $user->id,
            ]);
            return response()->json(['message' => 'Agendamento não encontrado.'], 404);
        }

        if ($appointment->user_id !== $user->id) {
            \Log::warning('Tentativa não autorizada de deletar agendamento', [
                'appointment_id' => $id,
                'appointment_user_id' => $appointment->user_id,
                'user_id' => $user->id,
            ]);
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        if ($appointment->status === 'canceled') {
            return response()->json(['message' => 'Já está cancelado.'], 422);
        }

        // ✅ AUDIT LOG: Registrar cancelamento
        \Log::channel('audit')->info('Agendamento cancelado', [
            'appointment_id' => $appointment->id,
            'user_id' => $user->id,
            'original_price' => $appointment->total_price,
            'timestamp' => now(),
        ]);

        if ($appointment->total_price == 0 && $appointment->notes && str_contains($appointment->notes, 'plano')) {
            $subscription = $user->activeSubscription;
            if ($subscription && $subscription->uses_this_month > 0) {
                $subscription->decrement('uses_this_month');
            }
        }

        $appointment->update(['status' => 'canceled']);

        return response()->json(['message' => 'Agendamento cancelado.']);
    }
}