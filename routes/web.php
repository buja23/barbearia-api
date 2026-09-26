<?php

use Illuminate\Support\Facades\Route;
use App\Models\Barbershop;
use App\Http\Controllers\MercadoPagoOAuthController;
use Symfony\Component\HttpFoundation\Cookie;

// Redirecionar raiz para painel admin
Route::get('/', function () {
    return view('landing');
});

// Rota pública para clientes
Route::get('/b/{slug}', function ($slug) {
    $barbershop = Barbershop::where('slug', $slug)->firstOrFail();
    
    // Aqui você retorna a view de agendamento dessa barbearia específica
    return view('barbershop.booking', ['barbershop' => $barbershop]);
})->name('barbershop.public');

// --- MercadoPago OAuth ---
// Auth é verificada dentro do controller (redireciona para /admin se não autenticado)
Route::get('/mp/connect', [MercadoPagoOAuthController::class, 'connect'])
    ->name('mp.connect');

Route::get('/mp/disconnect', [MercadoPagoOAuthController::class, 'disconnect'])
    ->name('mp.disconnect');

// O callback não tem auth pois o MP redireciona externamente
// A segurança é garantida pelo parâmetro `state` (CSRF)
Route::get('/mp/callback', [MercadoPagoOAuthController::class, 'callback'])
    ->name('mp.callback');

Route::get('/debug-cookie', function () {
    $file = null;
    $line = null;

    $alreadySent = headers_sent($file, $line);

    $response = response()->json([
        'headers_sent' => $alreadySent,
        'headers_sent_file' => $file,
        'headers_sent_line' => $line,
        'output_buffering' => ini_get('output_buffering'),
        'ob_level' => ob_get_level(),
        'php_headers' => headers_list(),
    ]);

    $response->headers->set('X-Debug-Test', 'funcionou');

    $response->headers->setCookie(
        \Symfony\Component\HttpFoundation\Cookie::create('manual_cookie')
            ->withValue('funcionou')
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite('lax')
    );

    return $response;
});

Route::get('/debug-internal', function () {
    $ch = curl_init('http://127.0.0.1/debug-cookie');

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);

    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return response()->json([
        'status' => $status,
        'error' => $error ?: null,
        'raw_response' => $response,
    ]);
});