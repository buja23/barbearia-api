<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Exceptions\MPApiException;

class PaymentService
{
    public function __construct()
    {
        // Inicializa a SDK v3
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.token', env('MERCADO_PAGO_ACCESS_TOKEN')));
    }

    /**
     * Gera um pagamento via PIX no Mercado Pago (SDK v3)
     */
    public function createPixPayment(Appointment $appointment): array
    {
        try {
            $client = new PaymentClient();

            // Monta o Payload da Requisição
            $request = [
                "transaction_amount" => (float) $appointment->total_price,
                "description" => "Corte Barbearia - Agendamento #" . $appointment->id,
                "payment_method_id" => "pix",
                "payer" => [
                    "email" => $appointment->user->email ?? 'cliente@barbearia.com',
                    "first_name" => $appointment->client_name ?? 'Cliente',
                ]
            ];

            // Gera uma chave de idempotência para evitar pagamentos duplicados
            $idempotencyKey = (string) $appointment->id . '_' . time();
            $requestOptions = new RequestOptions();
            $requestOptions->setCustomHeaders(["x-idempotency-key" => $idempotencyKey]);

            // Faz a chamada à API
            $payment = $client->create($request, $requestOptions);

            // Verifica se gerou o QR Code
            if (!isset($payment->point_of_interaction->transaction_data)) {
                throw new \Exception('O Mercado Pago não retornou os dados do Pix.');
            }

            $pixData = $payment->point_of_interaction->transaction_data;

            // Atualiza o agendamento
            $appointment->update([
                'payment_id' => (string) $payment->id,
                'payment_status' => $payment->status,
                'payment_method' => 'pix',
                'pix_copy_paste' => $pixData->qr_code,
                'pix_qr_code_url' => $pixData->qr_code_base64,
            ]);

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'qr_code' => $pixData->qr_code,
            ];

        } catch (MPApiException $e) {
            // Erro específico da API do Mercado Pago
            Log::error('Erro MercadoPago API: ' . json_encode($e->getApiResponse()->getContent()));
            return ['success' => false, 'error' => 'Erro ao processar pagamento.'];
        } catch (\Exception $e) {
            // Erros genéricos
            Log::error('Erro PaymentService: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}