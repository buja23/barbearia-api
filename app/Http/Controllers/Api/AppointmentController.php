<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Service;
use App\Models\Subscription; // Importação necessária para o Módulo 4
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request; // Importação vital para DB::transaction
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    public function getAvailableSlots(Request $request, BookingService $service, $slug = null)
    {

        $request->validate([
            'barber_id'  => 'required|exists:barbers,id',
            'date'       => 'required|date_format:Y-m-d',
            'service_id' => 'required|exists:services,id',
        ]);

        $barber = Barber::find($request->barber_id);
        $slots  = $service->getAvailableSlots($barber, $request->date, $request->service_id);

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

        // --- 🛡️ BLINDAGEM DE INTEGRIDADE (NOVO) ---
        $barber  = Barber::findOrFail($data['barber_id']);
        $service = Service::findOrFail($data['service_id']);

        // Verifica se o serviço pertence à mesma barbearia do barbeiro
        if ($barber->barbershop_id !== $service->barbershop_id) {
            return response()->json([
                'message' => 'Erro de segurança: O serviço e o barbeiro não pertencem à mesma barbearia.',
            ], 422);
        }
        // ------------------------------------------

        $user = $request->user();

        // 1. Busca a assinatura e o plano para checar o limite
        $subscription = $user->activeSubscription;
        $plan         = $subscription ? $subscription->plan : null;

        // 2. Verifica se ainda tem saldo de cortes (Uso < Limite)
        $hasBalance = $subscription && $plan && ($subscription->uses_this_month < $plan->monthly_limit);

        // 3. Define a Mensagem e o Preço
        if ($subscription && $hasBalance) {
            $finalPrice = 0.00;
            $message    = 'Agendado via assinatura!';
            $notes      = 'Agendado via assinatura';
        } elseif ($subscription && ! $hasBalance) {
            // Se tem assinatura mas NÃO tem saldo, ele paga o valor normal
            $finalPrice = $service->price;
            $message    = 'Agendado com sucesso! (Atenção: Limite do plano atingido, este serviço será cobrado à parte).';
            $notes      = 'Limite da assinatura atingido - Cobrança avulsa';
        } else {
            // Cliente sem assinatura
            $finalPrice = $service->price;
            $message    = 'Agendado com sucesso!';
            $notes      = null;
        }

        $start = Carbon::parse($data['scheduled_at']);
        $end   = $start->copy()->addMinutes($service->duration_minutes);

        return DB::transaction(function () use ($data, $user, $service, $end, $finalPrice, $subscription, $hasBalance, $message, $notes) {
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
                'notes'        => $notes,
            ]);

            // Se usou a assinatura, incrementamos o contador de uso imediatamente
            if ($subscription && $hasBalance) {
                $subscription->increment('uses_this_month');
            }

            return response()->json([
                'message'     => $message, // O Front-end vai ler este campo
                'appointment' => $appointment,
                'usage'       => $subscription ? [
                    'current' => $subscription->uses_this_month,
                    'limit'   => $subscription->plan->monthly_limit,
                ] : null,
            ], 201);
        });
    }

    public function destroy(Request $request, $id)
    {
        $appointment = Appointment::where('user_id', $request->user()->id)
            ->findOrFail($id);

        // 1. Regra de Negócio: Antecedência mínima para cancelamento (ex: 2 horas)
        $hoursNotice = now()->diffInHours($appointment->scheduled_at, false);

        if ($hoursNotice < 24) {
            return response()->json([
                'message' => 'Cancelamento não permitido com menos de 24 horas de antecedência.',
            ], 422);
        }

        if ($appointment->status === 'cancelled') {
            return response()->json(['message' => 'Este agendamento já está cancelado.'], 422);
        }

        return DB::transaction(function () use ($appointment, $request) {
            // 2. Lógica de Estorno para Assinantes
            // Se o preço foi 0.00 e tem a nota de assinatura, devolvemos o corte
            if ($appointment->total_price == 0.00 && str_contains($appointment->notes, 'assinatura')) {
                $subscription = $request->user()->activeSubscription;

                if ($subscription) {
                    $subscription->increment('remaining_cuts');
                }
            }

            // 3. Atualiza o status
            $appointment->update(['status' => 'cancelled']);

            return response()->json([
                'message'  => 'Agendamento cancelado com sucesso!',
                'refunded' => $appointment->total_price == 0.00,
            ]);
        });
    }

    public function index(Request $request)
    {
        $user = $request->user();

        // Buscamos todos os agendamentos do cliente ordenados
        // Carregamos 'barber.barbershop' porque o Resource usa o nome da barbearia
        $appointments = Appointment::with(['barber.barbershop', 'service'])
            ->where('user_id', $user->id)
            ->orderBy('scheduled_at', 'desc')
            ->get();

        // Separamos o joio do trigo (Futuros vs Passados)
        $upcoming = $appointments->filter(function ($app) {
            return $app->scheduled_at >= now() && ! in_array($app->status, ['cancelled', 'no_show', 'completed']);
        })->values();

        $history = $appointments->filter(function ($app) {
            return $app->scheduled_at < now() || in_array($app->status, ['cancelled', 'no_show', 'completed']);
        })->values();

        return response()->json([
            'upcoming' => \App\Http\Resources\AppointmentResource::collection($upcoming),
            'history'  => \App\Http\Resources\AppointmentResource::collection($history),
        ]);
    }

}
