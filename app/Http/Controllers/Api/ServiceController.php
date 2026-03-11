<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Barbershop;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Lista serviços de uma barbearia.
     * ✅ Com validação de slug e paginação
     */
    public function index(Request $request, $slug)
    {
        // ✅ Validação: Slug deve ser alfanumérico com hífens
        if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            return response()->json(['message' => 'Slug inválido'], 400);
        }

        $shop = Barbershop::where('slug', $slug)->first();

        if (!$shop) {
            return response()->json(['message' => 'Loja não encontrada'], 404);
        }

        // ✅ Busca serviços apenas ATIVOS desta loja
        $services = Service::where('barbershop_id', $shop->id)
            ->where('is_active', true)
            ->select(['id', 'name', 'price', 'duration_minutes', 'description']) // Apenas campos públicos
            ->orderBy('name', 'asc') // Ordem consistente
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'price' => number_format($service->price, 2, '.', ''),
                    'duration_minutes' => (int) $service->duration_minutes,
                    'description' => $service->description,
                ];
            });

        return response()->json($services);
    }
}