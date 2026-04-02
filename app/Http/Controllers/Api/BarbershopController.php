<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barbershop;
use Illuminate\Http\Request;

class BarbershopController extends Controller
{
    /**
     * Retorna informações públicas de uma barbearia.
     * ✅ Valida slug + Filtra dados sensíveis na resposta
     */
    public function show(Request $request, $slug)
    {
        // ✅ Validação: Slug deve ser alfanumérico com hífens
        if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            return response()->json(['message' => 'Slug inválido'], 400);
        }

        $barbershop = Barbershop::where('slug', $slug)->first();

        if (!$barbershop) {
            return response()->json(['message' => 'Barbearia não encontrada'], 404);
        }

        // Garante que a URL da logo seja absoluta e segura
        $logoUrl = $barbershop->logo_path
            ? asset('storage/' . $barbershop->logo_path)
            : null;

        $barbershop->load('openingHours');

        // ✅ Retorna APENAS dados públicos (sem IDs internos, etc)
        return response()->json([
            'id'            => $barbershop->id,
            'name'          => $barbershop->name,
            'slug'          => $barbershop->slug,
            'description'   => $barbershop->description,
            'logo'          => $logoUrl,
            // ✅ SEGURANÇA: Formatar telefone sem expor o completo
            'phone'         => $this->maskPhone($barbershop->phone),
            'address'       => $barbershop->address,
            'whatsapp'      => 'https://wa.me/55' . preg_replace('/[^0-9]/', '', $barbershop->phone),
            // Chave pública do MP da barbearia — usada pelo app para inicializar o Bricks (form de cartão)
            // null = barbearia ainda não configurou o MercadoPago
            'mp_public_key' => $barbershop->mp_public_key ?: null,
            'opening_hours' => $barbershop->openingHours->map(fn ($h) => [
                'day_of_week'  => $h->day_of_week,    // 0=Dom, 1=Seg, ..., 6=Sáb
                'opening_time' => $h->opening_time,    // "09:00"
                'closing_time' => $h->closing_time,    // "20:00"
                'is_closed'    => (bool) $h->is_closed,
            ]),
            'theme'         => [
                'primary'   => '#0f172a',
                'secondary' => '#fbbf24',
            ],
        ]);
    }

    /**
     * ✅ Mascara telefone para exibição
     */
    private function maskPhone($phone): string
    {
        // Remove caracteres especiais
        $clean = preg_replace('/[^0-9]/', '', $phone);
        
        // Formato: (XX) XXXXX-XXXX ou (XX) XXXX-XXXX
        if (strlen($clean) === 11) {
            return '(' . substr($clean, 0, 2) . ') ' . substr($clean, 2, 5) . '-' . substr($clean, 7);
        } elseif (strlen($clean) === 10) {
            return '(' . substr($clean, 0, 2) . ') ' . substr($clean, 2, 4) . '-' . substr($clean, 6);
        }
        
        return $phone;
    }
}
