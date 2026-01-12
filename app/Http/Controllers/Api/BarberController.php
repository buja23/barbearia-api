<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barber;

class BarberController extends Controller
{
    public function index()
    {
        // Retorna os barbeiros e a barbearia a que pertencem
        return response()->json(
            Barber::where('is_active', true)->with('barbershop')->get()
        );
    }
}