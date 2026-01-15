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


/* --- Rotas Públicas --- */
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/plans', [PlanController::class, 'index']);
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/barbers', [BarberController::class, 'index']);
Route::get('/barbershops/{slug}', [BarbershopController::class, 'show']);

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
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy']);
});