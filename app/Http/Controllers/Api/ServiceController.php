<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Barbershop;

class ServiceController extends Controller
{
    public function index($slug)
    {
        $shop = Barbershop::where('slug', $slug)->first();

        if (!$shop) {
            return response()->json(['message' => 'Loja não encontrada'], 404);
        }

        // Busca serviços apenas desta loja
        $services = Service::where('barbershop_id', $shop->id)->get();

        return response()->json($services);
    }
}