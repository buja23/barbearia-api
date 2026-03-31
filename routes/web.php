<?php

use Illuminate\Support\Facades\Route;
use App\Models\Barbershop;
use App\Http\Controllers\MercadoPagoOAuthController;

// Redirecionar raiz para painel admin
Route::get('/', function () {
    return redirect('/admin');
});

// Rota pública para clientes
Route::get('/b/{slug}', function ($slug) {
    $barbershop = Barbershop::where('slug', $slug)->firstOrFail();
    
    // Aqui você retorna a view de agendamento dessa barbearia específica
    return view('barbershop.booking', ['barbershop' => $barbershop]);
})->name('barbershop.public');

// --- MercadoPago OAuth ---
// Somente usuários autenticados no painel admin podem iniciar o fluxo
Route::middleware(['auth'])->group(function () {
    Route::get('/mp/connect', [MercadoPagoOAuthController::class, 'connect'])
        ->name('mp.connect');

    Route::get('/mp/disconnect', [MercadoPagoOAuthController::class, 'disconnect'])
        ->name('mp.disconnect');
});

// O callback NÃO tem middleware auth pois o MP redireciona externamente
// A segurança é garantida pelo parâmetro `state` (CSRF)
Route::get('/mp/callback', [MercadoPagoOAuthController::class, 'callback'])
    ->name('mp.callback');


