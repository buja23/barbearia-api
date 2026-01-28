<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barbershop;
use Illuminate\Http\Request;

class BarbershopController extends Controller
{
    // O App chama essa rota passando o SLUG (ex: /api/barbershop/barbearia-do-ze)
    public function show($slug)
    {
        $barbershop = Barbershop::where('slug', $slug)->first();

        if (!$barbershop) {
            return response()->json(['message' => 'Barbearia não encontrada'], 404);
        }

        return response()->json([
            'id'       => $barbershop->id,
            'name'     => $barbershop->name,
            'slug'     => $barbershop->slug,
            'logo'     => $barbershop->logo_path ? url('storage/' . $barbershop->logo_path) : null,
            'phone'    => $barbershop->phone,
            'address'  => $barbershop->address,
            'whatsapp' => 'https://wa.me/55' . preg_replace('/[^0-9]/', '', $barbershop->phone), // Link pronto pro botão do Zap
            
            // Aqui você pode retornar as configurações visuais se tiver no futuro
            'theme' => [
                'primary_color' => '#000000', // Exemplo
                'accent_color'  => '#D4AF37'  // Dourado
            ]
        ]);
    }
}