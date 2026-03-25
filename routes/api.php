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
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\ResetPasswordController;
// 👇 Adicione este import novo!
use App\Http\Controllers\Api\SubscriptionController; 

/* --- 1. Autenticação (Global) com Rate Limiting --- */
Route::middleware('throttle:' . env('RATE_LIMIT_AUTH', 5) . ',1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/password/forgot', [ForgotPasswordController::class, 'sendResetLink']);
    Route::post('/password/reset', [ResetPasswordController::class, 'reset']);
});

/* --- 2. Webhooks (Pagamentos) --- */
Route::middleware('throttle:60,1')->post('/webhooks/mercadopago', [WebhookController::class, 'handle']);

/* 
--- 3. ÁREA PROTEGIDA (Requer Login no App) --- 
   ✅ Com Rate Limiting por Usuário - Max 60 requests/min por user
*/
Route::middleware('auth:sanctum', 'throttle:60,1')->group(function () {
    
    Route::get('/user', fn (Request $request) => $request->user());
    Route::put('/user', [AuthController::class, 'update']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Assinaturas (Rate limit adicional - 10 por minuto)
    Route::middleware('throttle:10,1')->group(function () {
        Route::get('/user/subscription', [SubscriptionController::class, 'index']);
        Route::post('/subscribe', [SubscriptionController::class, 'store']);
        Route::post('/subscribe/cancel', [SubscriptionController::class, 'destroy']);
    });

    // Agendamentos (Rate limit adicional - 20 por minuto)
    Route::middleware('throttle:20,1')->group(function () {
        Route::get('/appointments', [AppointmentController::class, 'index']);      
        Route::post('/appointments', [AppointmentController::class, 'store']);     
        Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy']);
    });
});

/* 
--- 4. Área Pública da Barbearia (Baseada no Slug) ---
   ✅ Com Rate Limiting - Max 30 requests/min (Previne scraping)
*/
Route::prefix('{slug}')->middleware('throttle:30,1')->group(function () {
    Route::get('/', [BarbershopController::class, 'show']); 
    Route::get('/plans', [PlanController::class, 'index']);
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/barbers', [BarberController::class, 'index']);
    Route::get('/slots', [AppointmentController::class, 'getAvailableSlots']);
});
