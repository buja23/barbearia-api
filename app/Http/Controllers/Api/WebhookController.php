<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Subscription;
use App\Models\Barbershop;
use App\Models\SaasPlan;
use App\Notifications\AppointmentConfirmed;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MercadoPago\MercadoPagoConfig;

class WebhookController extends Controller
{
    public function __construct(private PaymentService $paymentService)
    {
        // Inicializa SDK com seu Token
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.token', env('MERCADO_PAGO_ACCESS_TOKEN')));
    }

    public function handle(Request $request)
    {
        // ✅ VALIDAÇÃO OBRIGATÓRIA: Confirmar que é um webhook legítimo do MP
        if (!$this->validateMercadoPagoSignature($request)) {
            Log::warning('Webhook inválido (assinatura falha):', $request->all());
            return response()->json(['error' => 'Invalid Signature'], 403);
        }

        Log::info('Webhook Recebido:', $request->all());

        try {
            $action = $request->input('action'); 
            $type   = $request->input('type'); // payment ou subscription_preapproval

            // --- CENÁRIO A: Renovação de Assinatura (Cartão) ---
            if ($type === 'subscription_preapproval' || $request->input('topic') === 'subscription') {
                return $this->handleSubscriptionRenewal($request);
            }

            // --- CENÁRIO B: Pagamento de Pix (Agendamento Avulso) ---
            if ($type === 'payment' || $request->input('topic') === 'payment') {
                return $this->handlePaymentUpdate($request);
            }

            return response()->json(['status' => 'ignored'], 200);

        } catch (\Exception $e) {
            Log::error('Erro Geral Webhook: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Valida a assinatura do webhook do Mercado Pago
     * Referência: https://www.mercadopago.com/developers/pt-br/docs/your-integrations/notifications/webhooks
     * Formato x-signature: ts=<timestamp>,v1=<hash>
     * Manifest: id:<data.id>;request-id:<x-request-id>;ts:<ts>;
     */
    protected function validateMercadoPagoSignature(Request $request): bool
    {
        $xSignature = $request->header('x-signature');
        $xRequestId = $request->header('x-request-id');
        $secret     = config('services.mercadopago.webhook_secret', env('MERCADO_PAGO_WEBHOOK_SECRET'));

        if (!$secret || !$xSignature || !$xRequestId) {
            return false;
        }

        // Extrai ts e v1 do header x-signature (formato: "ts=123,v1=abc")
        $parts = [];
        foreach (explode(',', $xSignature) as $part) {
            $kv = explode('=', $part, 2);
            if (count($kv) === 2) {
                $parts[trim($kv[0])] = trim($kv[1]);
            }
        }

        $ts = $parts['ts'] ?? null;
        $v1 = $parts['v1'] ?? null;

        if (!$ts || !$v1) {
            return false;
        }

        // Rejeita webhooks com timestamp mais antigo que 5 minutos (replay attack)
        if (abs(time() - (int) $ts) > 300) {
            Log::warning('Webhook rejeitado: timestamp fora da janela de 5 minutos', ['ts' => $ts]);
            return false;
        }

        // Constrói o manifest conforme documentação oficial do Mercado Pago
        $dataId   = $request->input('data.id') ?? '';
        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";

        $expectedHash = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expectedHash, $v1);
    }

    /**
     * Lógica de Ouro: Renova os créditos da assinatura
     */
    protected function handleSubscriptionRenewal(Request $request)
    {
        try {
            $externalId = $request->input('data.id') ?? $request->input('id');
            // Status authorized ou paused. Precisamos garantir que está ativo.
            // Nota: Em produção, idealmente consultamos a API do MP para confirmar o status real.
            
            // Busca a assinatura pelo ID do Mercado Pago (que salvaremos na contratação)
            $subscription = Subscription::where('external_id', $externalId)->first();

            if ($subscription) {
                // Se o Webhook diz que foi pago/renovado, resetamos o ciclo
                $subscription->update([
                    'status'          => 'active',
                    'uses_this_month' => 0, // Zera o contador de cortes
                    'expires_at'      => now()->addMonth(), // Dá mais 30 dias de vida
                ]);

                Log::info("Assinatura #{$subscription->id} renovada com sucesso via Webhook!");
                return response()->json(['status' => 'subscription_updated'], 200);
            }

            return response()->json(['status' => 'subscription_not_found'], 404);

        } catch (\Exception $e) {
            Log::error('Erro ao renovar assinatura: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sua lógica original de Pix (perfeita)
     */
    protected function handlePaymentUpdate(Request $request)
    {
        $paymentId = $request->input('data.id') ?? $request->input('id');

        if (!$paymentId) {
            return response()->json(['error' => 'No Payment ID'], 400);
        }

        // 1. Consulta o status real no Mercado Pago
        $payment = $this->paymentService->getPayment($paymentId);

        // 2a. Verifica se é pagamento SaaS (assinatura da plataforma)
        $barbershop = Barbershop::where('saas_payment_id', $paymentId)->first();
        if ($barbershop) {
            return $this->handleSaasPayment($payment, $barbershop);
        }

        $externalReference = (string) ($payment->external_reference ?? '');
        if (str_starts_with($externalReference, 'saas:')) {
            $segments = explode(':', $externalReference);
            $barbershopId = isset($segments[1]) ? (int) $segments[1] : null;
            $planId = isset($segments[2]) ? (int) $segments[2] : null;

            $barbershop = $barbershopId ? Barbershop::find($barbershopId) : null;

            if ($barbershop) {
                if ($planId) {
                    $barbershop->update(['saas_plan_id' => $planId]);
                    $barbershop->refresh();
                }

                return $this->handleSaasPayment($payment, $barbershop);
            }
        }

        // 2b. Busca o agendamento
        $appointment = Appointment::where('payment_id', $paymentId)->first();

        if ($appointment) {
            // 3. Atualiza os status
            $appointment->update([
                'payment_status' => $payment->status,
            ]);

            // Se aprovou, confirma e notifica
            if ($payment->status === 'approved' && $appointment->status !== 'confirmed') {
                $appointment->update(['status' => 'confirmed']);

                if ($appointment->user) {
                    $appointment->user->notify(new AppointmentConfirmed($appointment));
                }

                Log::info("Agendamento #{$appointment->id} confirmado via Pix!");
            }
            return response()->json(['status' => 'payment_updated'], 200);
        }

        return response()->json(['status' => 'payment_target_not_found'], 404);
    }

    /**
     * Ativa assinatura SaaS da plataforma quando pagamento PIX é aprovado.
     */
    protected function handleSaasPayment(object $payment, Barbershop $barbershop)
    {
        if ($payment->status !== 'approved') {
            return response()->json(['status' => 'saas_payment_pending'], 200);
        }

        if ($barbershop->saas_last_payment_id === (string) $payment->id) {
            return response()->json(['status' => 'saas_payment_already_processed'], 200);
        }

        $plan = $barbershop->saasPlan;
        $billingCycleMonths = max(1, (int) ($plan?->billing_cycle_months ?? 1));
        $baseDate = $barbershop->subscription_status === 'active'
            && $barbershop->subscription_expires_at?->isFuture()
            ? $barbershop->subscription_expires_at->copy()
            : now();
        $expiresAt = $baseDate->addMonthsNoOverflow($billingCycleMonths);

        $barbershop->update([
            'subscription_status'     => 'active',
            'subscription_expires_at' => $expiresAt,
            'subscription_plan'       => $plan?->name ?? 'Basico',
            'saas_payment_id'         => null,
            'saas_last_payment_id'    => (string) $payment->id,
            // Limpa dados do PIX após uso
            'saas_pix_copy_paste'     => null,
            'saas_pix_qr_code'        => null,
        ]);

        Log::channel('audit')->info('Assinatura SaaS ativada via webhook', [
            'barbershop_id' => $barbershop->id,
            'payment_id'    => $payment->id,
            'plan'          => $plan?->name,
            'expires_at'    => $expiresAt->toIso8601String(),
            'billing_cycle_months' => $billingCycleMonths,
        ]);

        return response()->json(['status' => 'saas_activated'], 200);
    }}