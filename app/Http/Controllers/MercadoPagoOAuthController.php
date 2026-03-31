<?php

namespace App\Http\Controllers;

use App\Models\Barbershop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MercadoPagoOAuthController extends Controller
{
    /**
     * Redireciona o barbeiro para a página de autorização do MercadoPago.
     */
    public function connect(Request $request)
    {
        if (!auth()->check()) {
            return redirect('/admin/login');
        }

        $user       = auth()->user();
        $barbershop = Barbershop::where('user_id', $user->id)->firstOrFail();

        // Gera estado aleatório para proteção CSRF
        $state = Str::random(40);
        $barbershop->update(['mp_oauth_state' => $state]);

        $appId       = config('services.mercadopago.app_id');
        $redirectUri = route('mp.callback');

        $url = 'https://auth.mercadopago.com.br/authorization'
            . '?client_id=' . $appId
            . '&response_type=code'
            . '&platform_id=mp'
            . '&redirect_uri=' . urlencode($redirectUri)
            . '&state=' . $state;

        return redirect($url);
    }

    /**
     * Recebe o código de autorização do MercadoPago após o barbeiro autorizar.
     * Troca pelo access token e salva no banco.
     */
    public function callback(Request $request)
    {
        $code  = $request->query('code');
        $state = $request->query('state');

        if (!$code || !$state) {
            return redirect('/admin/barbershops')
                ->with('error', 'Autorização cancelada ou inválida.');
        }

        // Encontra a barbearia pelo state CSRF
        $barbershop = Barbershop::where('mp_oauth_state', $state)->first();

        if (!$barbershop) {
            Log::warning('MP OAuth: state inválido ou expirado', ['state' => $state]);
            return redirect('/admin/barbershops')
                ->with('error', 'Link de autorização expirado. Tente novamente.');
        }

        // Troca o code pelo access_token
        $response = Http::asForm()->post('https://api.mercadopago.com/oauth/token', [
            'client_id'     => config('services.mercadopago.app_id'),
            'client_secret' => config('services.mercadopago.app_secret'),
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => route('mp.callback'),
        ]);

        if ($response->failed() || !$response->json('access_token')) {
            Log::error('MP OAuth: falha ao trocar code por token', [
                'barbershop_id' => $barbershop->id,
                'status'        => $response->status(),
                'body'          => $response->json(),
            ]);

            return redirect('/admin/barbershops')
                ->with('error', 'Falha ao conectar com o MercadoPago. Tente novamente.');
        }

        $data = $response->json();

        // Salva credenciais e limpa o state CSRF
        $barbershop->update([
            'mp_access_token' => $data['access_token'],
            'mp_public_key'   => $data['public_key'] ?? null,
            'mp_oauth_state'  => null,
        ]);

        Log::channel('audit')->info('MP OAuth conectado', [
            'barbershop_id' => $barbershop->id,
            'mp_user_id'    => $data['user_id'] ?? null,
        ]);

        return redirect('/admin/barbershops')
            ->with('success', '✅ MercadoPago conectado com sucesso! Sua barbearia já pode receber pagamentos.');
    }

    /**
     * Desconecta o MercadoPago da barbearia.
     */
    public function disconnect(Request $request)
    {
        if (!auth()->check()) {
            return redirect('/admin/login');
        }

        $user       = auth()->user();
        $barbershop = Barbershop::where('user_id', $user->id)->firstOrFail();

        $barbershop->update([
            'mp_access_token' => null,
            'mp_public_key'   => null,
            'mp_oauth_state'  => null,
        ]);

        Log::channel('audit')->info('MP OAuth desconectado', [
            'barbershop_id' => $barbershop->id,
            'user_id'       => $user->id,
        ]);

        return redirect('/admin/barbershops')
            ->with('success', 'MercadoPago desconectado.');
    }
}
