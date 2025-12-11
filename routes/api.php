<?php 

use App\Http\Controllers\Api\BarbershopController;
use Illuminate\Support\Facades\Route;

// Rota pública: O App não precisa estar logado para ler os dados da barbearia
Route::get('/barbershops/{slug}', [BarbershopController::class, 'show']);