<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Notifications\AppointmentConfirmed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;

class WebhookController extends Controller
{
    public function __construct()
    {
        // Inicializa SDK
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.token', env('MERCADO_PAGO_ACCESS_TOKEN')));
    }

    public function handle(Request $request)
    {
        // Log para debug (ver o que o Mercado Pago mandou)
        Log::info('Webhook Recebido:', $request->all());

        try {
            // O Mercado Pago manda o ID no query param ou no body
            // Geralmente vem como ?data.id=... ou body { data: { id: ... } }
            $paymentId = $request->input('data.id') ?? $request->input('id');
            $type      = $request->input('type');

            // Só nos interessa avisos de "payment"
            if ($type === 'payment' && $paymentId) {

                // 1. Consulta o status real no Mercado Pago (Segurança)
                $client  = new PaymentClient();
                $payment = $client->get($paymentId);

                // 2. Busca o agendamento no nosso banco
                $appointment = Appointment::where('payment_id', $paymentId)->first();

                if ($appointment) {
                    // 3. Atualiza os status
                    $appointment->update([
                        'payment_status' => $payment->status, // approved, pending, rejected
                    ]);

                    // Se aprovou, confirma o agendamento!
                    if ($payment->status === 'approved') {
                        $appointment->update(['status' => 'confirmed']);

                        // 🔥 DISPARA O E-MAIL
                        // Se o agendamento tem um usuário vinculado (User), notificamos ele.
                        // Se for agendamento avulso sem usuário, precisaríamos notificar via email string (routeNotificationFor),
                        // mas vamos focar no User registrado por enquanto.
                        if ($appointment->user) {
                            $appointment->user->notify(new AppointmentConfirmed($appointment));
                        }

                        Log::info("Agendamento #{$appointment->id} confirmado e notificado!");
                    }
                }
            }

            return response()->json(['status' => 'ok'], 200);

        } catch (\Exception $e) {
            Log::error('Erro Webhook: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Error'], 500);
        }
    }
}
