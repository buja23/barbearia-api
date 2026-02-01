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
// 👇 Adicione este import novo!
use App\Http\Controllers\Api\SubscriptionController; 

/* --- 1. Autenticação (Global) --- */
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

/* --- 2. Webhooks (Pagamentos) --- */
Route::post('/webhooks/mercadopago', [WebhookController::class, 'handle']);

/* 
--- 3. ÁREA PROTEGIDA (Requer Login no App) --- 
   👇 ESTE BLOCO VEIO PARA CIMA 👇
*/
Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/user', fn (Request $request) => $request->user());
    Route::put('/user', [AuthController::class, 'update']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Assinaturas
    Route::get('/user/subscription', [SubscriptionController::class, 'index']);
    Route::post('/subscribe', [SubscriptionController::class, 'store']); // Assumindo esta rota
    Route::post('/subscribe/cancel', [SubscriptionController::class, 'destroy']); // Assumindo esta rota

    // Agendamentos
    Route::get('/appointments', [AppointmentController::class, 'index']);      
    Route::post('/appointments', [AppointmentController::class, 'store']);     
    Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy']); 
});

/* 
--- 4. Área Pública da Barbearia (Baseada no Slug) --- 
   👇 ESTE BLOCO FOI PARA BAIXO 👇
*/
Route::prefix('{slug}')->group(function () {
    Route::get('/', [BarbershopController::class, 'show']); 
    Route::get('/plans', [PlanController::class, 'index']);
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/barbers', [BarberController::class, 'index']);
    Route::get('/slots', [AppointmentController::class, 'getAvailableSlots']);
});
