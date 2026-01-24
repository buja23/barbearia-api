<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BarberController;
use App\Http\Controllers\Api\BarbershopController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\CalendarController;
use Illuminate\Http\Request; // CORREÇÃO: Import necessário para a rota /user
use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\WebhookController;


/* --- Rotas Públicas --- */
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/plans', [PlanController::class, 'index']);
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/barbers', [BarberController::class, 'index']);
Route::get('/barbershops/{slug}', [BarbershopController::class, 'show']);

// Rota pública para o Mercado Pago (POST)
Route::post('/webhooks/mercadopago', [WebhookController::class, 'handle']);

/* --- Rotas do Calendário (Admin/Interno) --- */
Route::get('/calendar/events', [CalendarController::class, 'index']);
Route::post('/calendar/event', [CalendarController::class, 'store']);

/* --- Rotas Protegidas (Cliente Logado) --- */
Route::middleware('auth:sanctum')->group(function () {
    
    // Perfil do Usuário
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    // Agendamento Inteligente
    Route::get('/slots', [AppointmentController::class, 'getAvailableSlots']);
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy']);
});