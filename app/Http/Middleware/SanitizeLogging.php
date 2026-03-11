<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SanitizeLogging
{
    /**
     * Handle an incoming request.
     *
     * ✅ Filtra dados sensíveis antes de logar requisições
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Lista de campos sensíveis a filtrar
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'cpf',
            'phone',
            'api_key',
            'access_token',
            'secret',
            'token',
            'authorization',
            'credit_card',
            'card_number',
            'cvv',
            'exp_date',
        ];

        // Log da requisição (com filtro de dados sensíveis)
        if (config('app.debug')) {
            $input = $request->all();
            
            // Filtrar dados sensíveis
            foreach ($sensitiveFields as $field) {
                if ($request->has($field)) {
                    $input[$field] = '****REDACTED****';
                }
            }

            Log::debug('API Request', [
                'method' => $request->method(),
                'path' => $request->path(),
                'input' => $input,
                'ip' => $request->ip(),
            ]);
        }

        return $next($request);
    }
}
