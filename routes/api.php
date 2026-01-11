<?php 

use App\Http\Controllers\Api\BarbershopController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalendarController;

Route::get('/calendar/events', [CalendarController::class, 'index']);
Route::post('/calendar/event', [CalendarController::class, 'store']);
Route::get('/barbershops/{slug}', [BarbershopController::class, 'show']);