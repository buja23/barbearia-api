<?php

use Illuminate\Support\Facades\Route;
use App\Models\Barbershop;

// Rota pública para clientes
Route::get('/b/{slug}', function ($slug) {
    $barbershop = Barbershop::where('slug', $slug)->firstOrFail();
    
    // Aqui você retorna a view de agendamento dessa barbearia específica
    return view('barbershop.booking', ['barbershop' => $barbershop]);
})->name('barbershop.public');


