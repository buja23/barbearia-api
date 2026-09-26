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

Route::get('/debug-cookie', function () {
    return response('cookie teste')
        ->header(
            'Set-Cookie',
            'manual_cookie=funcionou; Path=/; Secure; HttpOnly; SameSite=Lax'
        );
});