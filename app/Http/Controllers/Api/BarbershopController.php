<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barbershop;

class BarbershopController extends Controller
{
    // O App chama essa rota passando o SLUG (ex: /api/barbershop/barbearia-do-ze)
    public function show($slug)
    {
        $barbershop = Barbershop::where('slug', $slug)->first();

        if (! $barbershop) {
            return response()->json(['message' => 'Barbearia não encontrada'], 404);
        }

        // Garante que a URL da logo seja absoluta e segura
        $logoUrl = $barbershop->logo_path
            ? asset('storage/' . $barbershop->logo_path)
            : null; // Ou coloque uma URL de imagem padrão aqui 'https://placehold.co/400'

        return response()->json([
            'id'       => $barbershop->id,
            'name'     => $barbershop->name,
            'slug'     => $barbershop->slug,
            'logo'     => $logoUrl,
            'phone'    => $barbershop->phone,
            'address'  => $barbershop->address,
            'whatsapp' => 'https://wa.me/55' . preg_replace('/[^0-9]/', '', $barbershop->phone),

            // Dica Senior: Envie as cores da marca para o App se pintar sozinho
            'theme'    => [
                'primary'   => '#0f172a', // Exemplo
                'secondary' => '#fbbf24',
            ],
        ]);
    }
}
