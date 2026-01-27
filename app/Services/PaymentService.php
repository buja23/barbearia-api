<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Exceptions\MPApiException;
use App\Models\Order;

class PaymentService
{
    public function __construct()
    {
        // Garante que o token venha do .env
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.token', env('MERCADO_PAGO_ACCESS_TOKEN')));
    }

    /**
     * Gera um pagamento via PIX no Mercado Pago (SDK v3)
     */
    public function createPixPayment(Appointment $appointment): array
    {
        try {
            $client = new PaymentClient();

            // Proteção: Preço deve ser positivo
            if ($appointment->total_price <= 0) {
                return ['success' => false, 'error' => 'Valor do agendamento inválido (R$ 0,00).'];
            }

            // --- AQUI ESTAVA O ERRO ---
            // O Mercado Pago exige CPF para testes de Pix
            // E o email não pode ser repetido/igual ao do vendedor
            
            $request = [
                "transaction_amount" => (float) $appointment->total_price,
                "description" => "Corte #" . $appointment->id,
                "payment_method_id" => "pix",
                "payer" => [
                    // TRUQUE 1: Email único a cada tentativa para não travar no Sandbox
                    "email" => "teste_user_" . uniqid() . "@test.com",
                    
                    "first_name" => "Cliente",
                    "last_name" => "Teste",
                    
                    // TRUQUE 2: CPF Válido de Teste (OBRIGATÓRIO)
                    "identification" => [
                        "type" => "CPF",
                        "number" => "19119119100" 
                    ]
                ]
            ];

            // Chave única para evitar duplicidade no MP
            $idempotencyKey = (string) $appointment->id . '_' . uniqid();
            
            $requestOptions = new RequestOptions();
            $requestOptions->setCustomHeaders(["x-idempotency-key" => $idempotencyKey]);

            // Faz a chamada à API
            $payment = $client->create($request, $requestOptions);

            if (!isset($payment->point_of_interaction->transaction_data)) {
                throw new \Exception('API não retornou dados do Pix.');
            }

            $pixData = $payment->point_of_interaction->transaction_data;

            // Salva no banco
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
            // Pega a resposta JSON real do erro
            $response = $e->getApiResponse()->getContent();
            Log::error('Erro MercadoPago (Payload): ' . json_encode($response));
            
            // Tenta pegar mensagem amigável
            $msg = $response['message'] ?? 'Erro desconhecido na API';
            return ['success' => false, 'error' => "Mercado Pago recusou: $msg"];
            
        } catch (\Exception $e) {
            Log::error('Erro Interno Payment: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function createOrderPix(Order $order): array
    {
        try {
            $client = new PaymentClient();

            $paymentData = [
                "transaction_amount" => (float) $order->total_amount,
                "description"        => "Venda #{$order->id} - Produtos",
                "payment_method_id"  => "pix",
                "payer" => [
                    "email" => "cliente@balcao.com", // Email genérico para venda balcão
                ]
            ];

            $payment = $client->create($paymentData);

            if ($payment->id) {
                $order->update([
                    'payment_id'       => $payment->id,
                    'status'           => $payment->status === 'approved' ? 'approved' : 'pending',
                    'pix_copy_paste'   => $payment->point_of_interaction->transaction_data->qr_code,
                    'qr_code_base64'   => $payment->point_of_interaction->transaction_data->qr_code_base64,
                ]);

                return ['success' => true];
            }

            return ['success' => false, 'error' => 'Falha ao obter ID do pagamento'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }


}