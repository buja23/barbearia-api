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
     * - Plano gratuito (price = 0) → ativa imediatamente.
     * - Plano pago → cria em status pending e gera PIX para pagamento.
     */
    public function store(StoreSubscriptionRequest $request)
    {
        $user = $request->user();

        // Impede múltiplas assinaturas ativas
        if ($user->activeSubscription) {
            return response()->json([
                'message' => 'Você já possui uma assinatura ativa.',
            ], 422);
        }

        $plan = Plan::where('id', $request->plan_id)
            ->where('is_active', true)
            ->firstOrFail();

        return DB::transaction(function () use ($user, $plan) {
            $isFree = (float) $plan->price === 0.0;

            $subscription = Subscription::create([
                'user_id'          => $user->id,
                'plan_id'          => $plan->id,
                'starts_at'        => now(),
                'expires_at'       => now()->addMonth(),
                'status'           => $isFree ? 'active' : 'pending',
                'uses_this_month'  => 0,
            ]);

            if ($isFree) {
                return response()->json([
                    'message'      => 'Assinatura ativada com sucesso!',
                    'subscription' => $subscription->load('plan'),
                ], 201);
            }

            // Plano pago: gera PIX
            $result = (new PaymentService())->createSubscriptionPix($subscription);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Falha ao gerar pagamento.');
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

        $subscription->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Assinatura cancelada com sucesso.']);
    }
}
