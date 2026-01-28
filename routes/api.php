<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BarbershopController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\BarberController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\WebhookController;

/* --- 1. Autenticação (Global) --- */
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

/* --- 2. Webhooks (Pagamentos) --- */
Route::post('/webhooks/mercadopago', [WebhookController::class, 'handle']);

/* --- 3. Área Pública da Barbearia (Baseada no Slug) --- */
// Tudo aqui é acessível sem login. Ex: api/barbearia-do-ze/services
Route::prefix('{slug}')->group(function () {
    
    // Dados da Loja (Logo, Cores, Contato)
    Route::get('/', [BarbershopController::class, 'show']); 
    
    // Catálogo
    Route::get('/plans', [PlanController::class, 'index']);
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/barbers', [BarberController::class, 'index']);

    // Disponibilidade (Movi para cá para permitir consultar sem logar)
    // O App manda: barber_id, service_id e date via Query Params
    Route::get('/slots', [AppointmentController::class, 'getAvailableSlots']);
});

/* --- 4. Área Protegida (Requer Login no App) --- */
Route::middleware('auth:sanctum')->group(function () {
    
    // Dados do Usuário Logado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/logout', [AuthController::class, 'logout']);

    // Gestão de Agendamentos (Aqui sim precisa ser seguro)
    Route::get('/appointments', [AppointmentController::class, 'index']);      // Meus agendamentos
    Route::post('/appointments', [AppointmentController::class, 'store']);     // Criar novo
    Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy']); // Cancelar
});