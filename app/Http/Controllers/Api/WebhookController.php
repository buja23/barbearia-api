<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Subscription;
use App\Models\Barbershop;
use App\Models\SaasPlan;
use App\Notifications\AppointmentConfirmed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;

class WebhookController extends Controller
{
    public function __construct()
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
     * Referência: https://www.mercadopago.com/developers/es/reference/webhooks/_api_v1_suscriptions_search/get_webhook_app_header_x_signature
     */
    protected function validateMercadoPagoSignature(Request $request): bool
    {
        $signature = $request->header('x-signature');
        $timestamp = $request->header('x-request-id');
        $secret = env('MERCADO_PAGO_WEBHOOK_SECRET');

        // Se não tiver segredo configurado, retorna falso (segurança padrão)
        if (!$secret || !$signature || !$timestamp) {
            return false; // Log::warning('Missing webhook headers');
        }

        // Mercado Pago usa HMAC SHA256
        $body = $request->getContent();
        $data = "{$timestamp}.{$body}";
        $expectedHash = hash_hmac('sha256', $data, $secret);
        $receivedHash = explode(',', $signature)[0] ?? '';

        return hash_equals($expectedHash, $receivedHash);
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
        $client  = new PaymentClient();
        $payment = $client->get($paymentId);

        // 2a. Verifica se é pagamento SaaS (assinatura da plataforma)
        $barbershop = Barbershop::where('saas_payment_id', $paymentId)->first();
        if ($barbershop) {
            return $this->handleSaasPayment($payment, $barbershop);
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

        return response()->json(['status' => 'appointment_not_found'], 404);
    }

    /**
     * Ativa assinatura SaaS da plataforma quando pagamento PIX é aprovado.
     */
    protected function handleSaasPayment(object $payment, Barbershop $barbershop)
    {
        if ($payment->status !== 'approved') {
            return response()->json(['status' => 'saas_payment_pending'], 200);
        }

        $plan = $barbershop->saasPlan;

        $barbershop->update([
            'subscription_status'     => 'active',
            'subscription_expires_at' => now()->addMonth(),
            'subscription_plan'       => $plan?->name ?? 'Pro',
            // Limpa dados do PIX após uso
            'saas_pix_copy_paste'     => null,
            'saas_pix_qr_code'        => null,
        ]);

        Log::channel('audit')->info('Assinatura SaaS ativada via webhook', [
            'barbershop_id' => $barbershop->id,
            'payment_id'    => $payment->id,
            'plan'          => $plan?->name,
            'expires_at'    => now()->addMonth()->toIso8601String(),
        ]);

        return response()->json(['status' => 'saas_activated'], 200);
    }}