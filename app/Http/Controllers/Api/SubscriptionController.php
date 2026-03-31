<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    /**
     * Retorna a assinatura ativa do usuário com detalhes do plano.
     */
    public function index(Request $request)
    {
        $subscription = $request->user()
            ->activeSubscription()
            ->with('plan')
            ->first();

        if (!$subscription) {
            return response()->json(['message' => 'Nenhuma assinatura ativa.'], 404);
        }

        return response()->json($subscription);
    }

    /**
     * Cria uma nova assinatura para o usuário.
     *
     * - Plano gratuito (price = 0)  → ativa imediatamente, sem pagamento.
     * - Plano pago + payment_method = "pix"  → gera PIX na conta do barbeiro.
     * - Plano pago + payment_method = "card" → cobra via cartão na conta do barbeiro.
     */
    public function store(StoreSubscriptionRequest $request)
    {
        $user = $request->user();

        if ($user->activeSubscription) {
            return response()->json([
                'message' => 'Você já possui uma assinatura ativa.',
            ], 422);
        }

        $plan = Plan::with('barbershop')
            ->where('id', $request->plan_id)
            ->where('is_active', true)
            ->firstOrFail();

        return DB::transaction(function () use ($user, $plan, $request) {
            $isFree        = (float) $plan->price === 0.0;
            $paymentMethod = $request->input('payment_method', 'pix'); // pix é padrão

            if (!$user->barbershop_id) {
                $user->update(['barbershop_id' => $plan->barbershop_id]);
            }

            $subscription = Subscription::create([
                'user_id'         => $user->id,
                'plan_id'         => $plan->id,
                'barbershop_id'   => $plan->barbershop_id,
                'starts_at'       => now(),
                'expires_at'      => now()->addMonth(),
                'status'          => $isFree ? 'active' : 'pending',
                'uses_this_month' => 0,
                'remaining_cuts'  => $plan->cuts_per_month ?? 0,
            ]);

            if ($isFree) {
                return response()->json([
                    'message'      => 'Assinatura ativada com sucesso!',
                    'subscription' => $subscription->load('plan'),
                ], 201);
            }

            $paymentService = new PaymentService();

            // --- CARTÃO ---
            if ($paymentMethod === 'card') {
                $result = $paymentService->createSubscriptionCard(
                    $subscription,
                    $request->input('card_token'),
                    (int) $request->input('installments', 1)
                );

                if (!$result['success']) {
                    throw new \Exception($result['error'] ?? 'Falha ao processar cartão.');
                }

                // Se aprovado imediatamente, ativa a assinatura
                if (($result['payment_status'] ?? '') === 'approved') {
                    $subscription->update(['status' => 'active']);
                }

                return response()->json([
                    'message'        => 'Pagamento processado! Sua assinatura está ' .
                        (($result['payment_status'] ?? '') === 'approved' ? 'ativa.' : 'em análise.'),
                    'subscription'   => $subscription->fresh()->load('plan'),
                    'payment_status' => $result['payment_status'],
                ], 201);
            }

            // --- PIX (padrão) ---
            $result = $paymentService->createSubscriptionPix($subscription);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Falha ao gerar PIX.');
            }

            return response()->json([
                'message'      => 'Assinatura criada. Efetue o pagamento via PIX para ativar.',
                'subscription' => $subscription->fresh()->load('plan'),
                'pix'          => [
                    'copy_paste' => $result['qr_code'],
                    'payment_id' => $result['payment_id'],
                ],
            ], 201);
        });
    }

    /**
     * Cancela a assinatura ativa do usuário.
     */
    public function destroy(Request $request)
    {
        $subscription = $request->user()->activeSubscription;

        if (!$subscription) {
            return response()->json(['message' => 'Nenhuma assinatura ativa para cancelar.'], 404);
        }

        $subscription->update(['status' => 'canceled']);

        return response()->json(['message' => 'Assinatura cancelada com sucesso.']);
    }
}
