<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class BarberController extends Controller
{
// Adicione o parâmetro $slug
    public function index($slug)
    {
        $barbershop = \App\Models\Barbershop::where('slug', $slug)->firstOrFail();

        // Retorna APENAS os barbeiros desta barbearia específica
        $barbers = $barbershop->barbers()
            ->where('is_active', true)
            ->get();

        return \App\Http\Resources\BarberResource::collection($barbers);
    }
}
