<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barbershop;
use Illuminate\Http\JsonResponse;

class BarbershopController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        // 1. Busca a barbearia pelo slug (ex: "barba-branca")
        // 2. Já traz junto os serviços ativos (para não fazer duas consultas)
        $barbershop = Barbershop::with(['services' => function($query) {
            $query->where('is_active', true);
        }])->where('slug', $slug)->firstOrFail();

        return response()->json([
            'data' => $barbershop,
        ]);
    }
}