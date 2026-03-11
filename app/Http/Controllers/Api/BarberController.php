<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Barber;
use App\Models\Barbershop;

class BarberController extends Controller
{
    /**
     * Lista barbeiros de uma barbearia.
     * ✅ Com validação de slug e filtro de dados sensíveis
     */
    public function index(Request $request, $slug)
    {
        // ✅ Validação: Slug deve ser alfanumérico com hífens
        if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            return response()->json(['message' => 'Slug inválido'], 400);
        }

        // Primeiro achamos a barbearia pelo slug
        $shop = Barbershop::where('slug', $slug)->first();

        if (!$shop) {
            return response()->json(['message' => 'Barbearia não encontrada'], 404);
        }

        // Pegamos os barbeiros DESSA barbearia
        $barbers = Barber::where('barbershop_id', $shop->id)
            ->where('is_active', true) // ✅ Apenas barbeiros ativos
            ->select(['id', 'name', 'avatar', 'email']) // ✅ Apenas campos públicos
            ->get();

        return response()->json($barbers);
    }
}