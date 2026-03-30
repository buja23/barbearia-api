<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Exceptions\MPApiException;
use App\Models\Order;
use App\Models\Barbershop;
use App\Models\SaasPlan;
use Piggly\Pix\StaticPayload;
use Piggly\Pix\Parser;

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

            // Obter dados do cliente da nomeação ou usuário
            $user = $appointment->user;
            $cpf = $user->cpf ?? env('MERCADOS_PAGO_TEST_CPF', '19119119100');
            
            $request = [
                "transaction_amount" => (float) $appointment->total_price,
                "description" => "Corte #" . $appointment->id,
                "payment_method_id" => "pix",
                "payer" => [
                    // Email único a cada tentativa para não travar no Sandbox
                    "email" => $user->email ?? ("cliente_" . uniqid() . "@test.com"),
                    
                    "first_name" => $user->name ?? "Cliente",
                    "last_name" => "Teste",
                    
                    // CPF (ideal: do usuário; teste: da variável de ambiente)
                    "identification" => [
                        "type" => "CPF",
                        "number" => preg_replace('/\D/', '', $cpf) // Remove formatação
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

            // ✅ AUDIT LOG: Registrar criação de pagamento
            Log::channel('audit')->info('Pagamento PIX criado', [
                'payment_id' => (string) $payment->id,
                'appointment_id' => $appointment->id,
                'user_id' => $appointment->user_id,
                'amount' => $appointment->total_price,
                'timestamp' => now(),
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

    /**
     * Gera um pagamento via PIX para ativação de assinatura.
     */
    public function createSubscriptionPix(Subscription $subscription): array
    {
        try {
            $client = new PaymentClient();
            $user   = $subscription->user;
            $plan   = $subscription->plan;
            $cpf    = $user->cpf ?? env('MERCADOS_PAGO_TEST_CPF', '19119119100');

            $paymentData = [
                'transaction_amount' => (float) $plan->price,
                'description'        => 'Assinatura - ' . $plan->name,
                'payment_method_id'  => 'pix',
                'payer'              => [
                    'email'          => $user->email,
                    'first_name'     => $user->name,
                    'last_name'      => 'Assinante',
                    'identification' => [
                        'type'   => 'CPF',
                        'number' => preg_replace('/\D/', '', $cpf),
                    ],
                ],
            ];

            $idempotencyKey = 'sub_' . $subscription->id . '_' . uniqid();
            $requestOptions = new \MercadoPago\Client\Common\RequestOptions();
            $requestOptions->setCustomHeaders(['x-idempotency-key' => $idempotencyKey]);

            $payment = $client->create($paymentData, $requestOptions);
            $pixData = $payment->point_of_interaction->transaction_data ?? null;

            if (!$pixData) {
                throw new \Exception('API não retornou dados do Pix.');
            }

            // Salva o external_id para o webhook de renovação encontrar a assinatura
            $subscription->update([
                'external_id' => (string) $payment->id,
            ]);

            Log::channel('audit')->info('PIX de assinatura criado', [
                'payment_id'      => (string) $payment->id,
                'subscription_id' => $subscription->id,
                'user_id'         => $user->id,
                'amount'          => $plan->price,
            ]);

            return [
                'success'    => true,
                'payment_id' => $payment->id,
                'qr_code'    => $pixData->qr_code,
            ];

        } catch (\MercadoPago\Exceptions\MPApiException $e) {
            $response = $e->getApiResponse()->getContent();
            Log::error('Erro MercadoPago (Assinatura): ' . json_encode($response));
            $msg = $response['message'] ?? 'Erro desconhecido na API';

            return ['success' => false, 'error' => "Mercado Pago recusou: $msg"];

        } catch (\Exception $e) {
            Log::error('Erro interno (Assinatura PIX): ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Busca os dados de um pagamento no Mercado Pago pelo ID.
     * Extraído em método para permitir substituição em testes.
     */
    public function getPayment(string $paymentId): object
    {
        $client = new PaymentClient();
        return $client->get($paymentId);
    }

    public function createOrderPix(Order $order): array
    {
        try {
            $barbershop = $order->barbershop;

            if (empty($barbershop?->pix_key)) {
                return [
                    'success' => false,
                    'error'   => 'Esta barbearia não configurou uma chave PIX. Acesse Configurações > Minha Barbearia.',
                ];
            }

            if ($order->total_amount <= 0) {
                return ['success' => false, 'error' => 'Valor da venda inválido.'];
            }

            $typeMap = [
                'cpf'    => Parser::KEY_TYPE_DOCUMENT,
                'cnpj'   => Parser::KEY_TYPE_DOCUMENT,
                'email'  => Parser::KEY_TYPE_EMAIL,
                'phone'  => Parser::KEY_TYPE_PHONE,
                'random' => Parser::KEY_TYPE_RANDOM,
            ];
            $keyType = $typeMap[$barbershop->pix_key_type ?? 'email'] ?? Parser::KEY_TYPE_EMAIL;

            $payload = (new StaticPayload())
                ->setMerchantName($barbershop->name)
                ->setMerchantCity('Brasil')
                ->setPixKey($keyType, $barbershop->pix_key)
                ->setAmount((float) $order->total_amount)
                ->getPixCode();

            $png = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(300)->margin(2)->generate($payload);
            $imgSrc = 'data:image/png;base64,' . base64_encode($png);

            $order->update([
                'payment_id'     => null,
                'status'         => 'pending',
                'pix_copy_paste' => $payload,
                'qr_code_base64' => $imgSrc,
            ]);

            Log::channel('audit')->info('PIX estático gerado para venda', [
                'order_id'      => $order->id,
                'barbershop_id' => $barbershop->id,
                'amount'        => $order->total_amount,
            ]);

            return ['success' => true];

        } catch (\Exception $e) {
            Log::error('Erro ao gerar PIX da venda: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Gera PIX para contratação do plano SaaS da plataforma (dono da barbearia).
     * O webhook confirma e ativa a barbearia com subscription_status='active'.
     */
    public function createSaasPix(Barbershop $barbershop, SaasPlan $plan): array
    {
        try {
            $client = new PaymentClient();
            $user   = $barbershop->user;
            $cpf    = $user->cpf ?? env('MERCADOS_PAGO_TEST_CPF', '19119119100');

            $paymentData = [
                'transaction_amount' => (float) $plan->price,
                // Prefix SAAS: permite o webhook identificar este tipo de pagamento
                'description'        => 'SAAS:' . $barbershop->id . ':' . $plan->id . ' - ' . $plan->name,
                'payment_method_id'  => 'pix',
                'payer'              => [
                    'email'          => $user->email,
                    'first_name'     => $user->name,
                    'last_name'      => 'Barbearia',
                    'identification' => [
                        'type'   => 'CPF',
                        'number' => preg_replace('/\D/', '', $cpf),
                    ],
                ],
            ];

            $idempotencyKey = 'saas_' . $barbershop->id . '_' . $plan->id . '_' . uniqid();
            $requestOptions = new RequestOptions();
            $requestOptions->setCustomHeaders(['x-idempotency-key' => $idempotencyKey]);

            $payment = $client->create($paymentData, $requestOptions);
            $pixData = $payment->point_of_interaction->transaction_data ?? null;

            if (!$pixData) {
                throw new \Exception('API não retornou dados do PIX.');
            }

            // Persiste dados do pagamento na barbearia para polling e webhook
            $barbershop->update([
                'saas_plan_id'        => $plan->id,
                'saas_payment_id'     => (string) $payment->id,
                'saas_pix_copy_paste' => $pixData->qr_code,
                'saas_pix_qr_code'    => $pixData->qr_code_base64,
            ]);

            Log::channel('audit')->info('PIX SaaS criado', [
                'payment_id'    => (string) $payment->id,
                'barbershop_id' => $barbershop->id,
                'plan_id'       => $plan->id,
                'amount'        => $plan->price,
            ]);

            return [
                'success'        => true,
                'payment_id'     => (string) $payment->id,
                'qr_code'        => $pixData->qr_code,
                'qr_code_base64' => $pixData->qr_code_base64,
            ];

        } catch (MPApiException $e) {
            $response = $e->getApiResponse()->getContent();
            Log::error('Erro MercadoPago (SaaS PIX): ' . json_encode($response));
            $msg = $response['message'] ?? 'Erro desconhecido na API';
            if (str_contains($msg, 'QR render') || str_contains($msg, 'key enabled')) {
                $msg = 'Sua conta Mercado Pago não possui chave PIX cadastrada. Acesse mercadopago.com.br, registre uma chave PIX e tente novamente. No momento, utilize o pagamento com cartão.';
            }
            return ['success' => false, 'error' => "Mercado Pago recusou: $msg"];

        } catch (\Exception $e) {
            Log::error('Erro interno (SaaS PIX): ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function createSaasCardCheckout(Barbershop $barbershop, SaasPlan $plan): array
    {
        try {
            $client = new PreferenceClient();
            $user = $barbershop->user;
            $billingUrl = route('filament.admin.pages.billing', ['tenant' => $barbershop->slug]);
            $externalReference = 'saas:' . $barbershop->id . ':' . $plan->id . ':' . uniqid();

            $request = [
                'external_reference' => $externalReference,
                'notification_url' => url('/api/webhooks/mercadopago'),
                'back_urls' => [
                    'success' => $billingUrl . '?checkout=success',
                    'failure' => $billingUrl . '?checkout=failure',
                    'pending' => $billingUrl . '?checkout=pending',
                ],
                'auto_return' => 'approved',
                'statement_descriptor' => 'BARBEARIA SAAS',
                'binary_mode' => true,
                'metadata' => [
                    'context' => 'saas',
                    'barbershop_id' => $barbershop->id,
                    'saas_plan_id' => $plan->id,
                ],
                'payer' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'items' => [
                    [
                        'id' => (string) $plan->id,
                        'title' => 'Plano ' . $plan->name,
                        'description' => 'Assinatura ' . $plan->billing_cycle_label . ' da plataforma para ' . $barbershop->name,
                        'category_id' => 'services',
                        'quantity' => 1,
                        'currency_id' => 'BRL',
                        'unit_price' => (float) $plan->price,
                    ],
                ],
                'payment_methods' => [
                    'excluded_payment_methods' => [
                        ['id' => 'pix'],
                    ],
                    'excluded_payment_types' => [
                        ['id' => 'ticket'],
                        ['id' => 'atm'],
                        ['id' => 'digital_currency'],
                    ],
                    'installments' => 12,
                    'default_installments' => 1,
                ],
            ];

            $preference = $client->create($request);

            $barbershop->update([
                'saas_plan_id' => $plan->id,
            ]);

            Log::channel('audit')->info('Checkout SaaS com cartao criado', [
                'barbershop_id' => $barbershop->id,
                'plan_id' => $plan->id,
                'preference_id' => $preference->id,
                'external_reference' => $externalReference,
                'amount' => $plan->price,
            ]);

            return [
                'success' => true,
                'checkout_url' => $preference->init_point,
            ];
        } catch (MPApiException $e) {
            $response = $e->getApiResponse()->getContent();
            Log::error('Erro MercadoPago (SaaS cartao): ' . json_encode($response));
            $msg = $response['message'] ?? 'Erro desconhecido na API';

            return ['success' => false, 'error' => "Mercado Pago recusou: $msg"];
        } catch (\Exception $e) {
            Log::error('Erro interno (SaaS cartao): ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Gera um pagamento PIX estático usando a chave PIX cadastrada na barbearia.
     * Dinheiro vai diretamente para o dono, sem intermediários.
     */
    public function generateLocalPixPayment(Appointment $appointment): array
    {
        $barbershop = $appointment->barbershop;

        if (empty($barbershop?->pix_key)) {
            return [
                'success' => false,
                'error'   => 'Esta barbearia nao configurou uma chave PIX. Acesse Configuracoes > Minha Barbearia.',
            ];
        }

        if ($appointment->total_price <= 0) {
            return ['success' => false, 'error' => 'Valor do agendamento invalido (R$ 0,00).'];
        }

        try {
            $typeMap = [
                'cpf'    => Parser::KEY_TYPE_DOCUMENT,
                'cnpj'   => Parser::KEY_TYPE_DOCUMENT,
                'email'  => Parser::KEY_TYPE_EMAIL,
                'phone'  => Parser::KEY_TYPE_PHONE,
                'random' => Parser::KEY_TYPE_RANDOM,
            ];
            $keyType = $typeMap[$barbershop->pix_key_type ?? 'email'] ?? Parser::KEY_TYPE_EMAIL;

            $payload = (new StaticPayload())
                ->setMerchantName($barbershop->name)
                ->setMerchantCity('Brasil')
                ->setPixKey($keyType, $barbershop->pix_key)
                ->setAmount((float) $appointment->total_price)
                ->getPixCode();

            $png    = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(300)->margin(2)->generate($payload);
            $imgSrc = 'data:image/png;base64,' . base64_encode($png);

            $appointment->update([
                'payment_method'   => 'pix',
                'payment_status'   => 'pending',
                'pix_copy_paste'   => $payload,
                'pix_qr_code_url'  => $imgSrc,
            ]);

            Log::channel('audit')->info('PIX estatico gerado', [
                'appointment_id' => $appointment->id,
                'barbershop_id'  => $barbershop->id,
                'amount'         => $appointment->total_price,
            ]);

            return ['success' => true];

        } catch (\Exception $e) {
            Log::error('Erro ao gerar PIX estatico: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

}