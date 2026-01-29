<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Barber;
use App\Models\Barbershop; // <--- Importante importar isso!

class BarberController extends Controller
{
    public function index($slug)
    {
        // 1. Primeiro achamos a barbearia pelo slug
        $shop = Barbershop::where('slug', $slug)->first();

        if (!$shop) {
            return response()->json(['message' => 'Barbearia não encontrada'], 404);
        }

        // 2. Agora pegamos os barbeiros DESSA barbearia
        $barbers = Barber::where('barbershop_id', $shop->id)->get();

        return response()->json($barbers);
    }
}