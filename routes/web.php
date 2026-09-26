<?php

use Illuminate\Support\Facades\Route;
use App\Models\Barbershop;
use App\Http\Controllers\MercadoPagoOAuthController;

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

Route::get('/debug-session', function () {
    session(['teste_sessao' => 'funcionando']);

    return response()->json([
        'session_driver' => config('session.driver'),
        'session_domain' => config('session.domain'),
        'session_secure' => config('session.secure'),
        'session_same_site' => config('session.same_site'),
        'session_cookie' => config('session.cookie'),
        'session_id' => session()->getId(),
        'session_value' => session('teste_sessao'),
        'request_secure' => request()->isSecure(),
    ]);
});
