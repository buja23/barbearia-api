<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Service;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    /**
     * Busca horários livres para agendamento.
     */
    public function getAvailableSlots(Request $request, $slug = null)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'barber_id' => 'required|integer',
            'service_id' => 'required|integer'
        ]);

        $date = $request->query('date');
        $barberId = $request->query('barber_id');
        $serviceId = $request->query('service_id');

        $startOfDay = Carbon::parse("$date 09:00:00");
        $endOfDay = Carbon::parse("$date 19:00:00");

        $service = Service::find($serviceId);
        // Tenta ler duration_minutes (snake) ou durationMinutes (camel) ou usa 30 padrão
        $duration = $service ? ($service->duration_minutes ?? $service->durationMinutes ?? 30) : 30;

        $busySlots = Appointment::where('barber_id', $barberId)
            ->whereDate('scheduled_at', $date)
            ->where('status', '!=', 'cancelled')
            ->get();

        $slots = [];
        $current = $startOfDay->copy();

        while ($current->lt($endOfDay)) {
            $slotStart = $current->copy();
            $slotEnd = $current->copy()->addMinutes($duration);

            $isBusy = $busySlots->contains(function ($appointment) use ($slotStart, $slotEnd) {
                $appStart = Carbon::parse($appointment->scheduled_at);
                // Se não tiver end_at, assume duração padrão de 30min para verificação
                $appEnd = $appointment->end_at ? Carbon::parse($appointment->end_at) : $appStart->copy()->addMinutes(30);

                return $slotStart->lt($appEnd) && $slotEnd->gt($appStart);
            });

            if (!$isBusy && $slotEnd->lte($endOfDay)) {
                $slots[] = $slotStart->format('H:i');
            }

            $current->addMinutes($duration);
        }

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
    public function store(Request $request)
    {
        // ✅ Validações Fortes
        $data = $request->validate([
            'barber_id'    => 'required|integer|exists:barbers,id',
            'service_id'   => 'required|integer|exists:services,id',
            'scheduled_at' => [
                'required',
                'date_format:Y-m-d H:i:s',
                'after:now', // Não pode agendar no passado
                'before:+1 year', // Máximo 1 ano no futuro
            ],
            'client_phone' => [
                'nullable',
                'regex:/^\+?[\d\s\-\(\)]{10,20}$/', // Valida formato de telefone
            ],
        ], [
            'scheduled_at.after' => 'A data/hora deve ser no futuro.',
            'scheduled_at.before' => 'Não é permitido agendar com mais de 1 ano de antecedência.',
            'client_phone.regex' => 'Formato de telefone inválido.',
        ]);

        $user = $request->user();
        $barber = Barber::findOrFail($data['barber_id']);
        $service = Service::findOrFail($data['service_id']);

        // Verifica assinatura
        $subscription = $user->activeSubscription; 
        $plan = $subscription ? $subscription->plan : null;
        
        // Verifica o nome correto da coluna de limite no banco
        $limit = 0;
        if ($plan) {
            $limit = $plan->monthly_limit ?? $plan->cuts_per_month ?? 999;
        }
        
        $hasBalance = $subscription && ($subscription->uses_this_month < $limit);

        $finalPrice = $service->price;
        $notes = null;
        $message = 'Agendamento confirmado!';

        if ($subscription && $hasBalance) {
            $finalPrice = 0.00;
            $message = 'Agendado via assinatura!';
            $notes = 'Pago pelo plano ' . $plan->name;
        } elseif ($subscription && !$hasBalance) {
            $message = 'Limite do plano atingido. Cobrança avulsa gerada.';
            $notes = 'Excedente do plano';
        }

        $start = Carbon::parse($data['scheduled_at']);
        $duration = $service->duration_minutes ?? 30;
        $end = $start->copy()->addMinutes($duration);

        return DB::transaction(function () use ($data, $user, $barber, $service, $start, $end, $finalPrice, $subscription, $hasBalance, $message, $notes) {
            
            $appointment = Appointment::create([
                'user_id'      => $user->id,
                'barber_id'    => $barber->id,
                'service_id'   => $service->id,
                // 'barbershop_id' => $barber->barbershop_id, // REMOVIDO PARA EVITAR ERRO 500
                'client_name'  => $user->name,
                'client_phone' => $data['client_phone'] ?? $user->phone ?? 'Não informado',
                'scheduled_at' => $start,
                'end_at'       => $end,
                'total_price'  => $finalPrice,
                'status'       => 'confirmed',
                'notes'        => $notes,
            ]);

            if ($subscription && $hasBalance) {
                $subscription->increment('uses_this_month');
            }

            return response()->json([
                'message' => $message,
                'appointment' => $appointment
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

        if ($appointment->status === 'cancelled') {
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

        $appointment->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Agendamento cancelado.']);
    }
}