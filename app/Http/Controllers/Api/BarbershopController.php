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

        // ✅ Retorna APENAS dados públicos (sem IDs internos, etc)
        return response()->json([
            'id'       => $barbershop->id,
            'name'     => $barbershop->name,
            'slug'     => $barbershop->slug,
            'logo'     => $logoUrl,
            // ✅ SEGURANÇA: Formatar telefone sem expor o completo
            'phone'    => $this->maskPhone($barbershop->phone),
            'address'  => $barbershop->address,
            'whatsapp' => 'https://wa.me/55' . preg_replace('/[^0-9]/', '', $barbershop->phone),
            'theme'    => [
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
