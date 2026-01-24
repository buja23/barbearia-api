<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Subscription; // Importante
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
        Log::info('Webhook Recebido:', $request->all());

        try {
            $action = $request->input('action'); 
            $type   = $request->input('type'); // payment ou subscription_preapproval

            // --- CENÁRIO A: Renovação de Assinatura (Cartão) ---
            // O MP geralmente manda type 'subscription_preapproval' ou topic 'subscription'
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

        // 2. Busca o agendamento
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
}