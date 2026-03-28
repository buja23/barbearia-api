<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessSubscriptionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        // Se não há usuário autenticado, deixa o Filament tratar (redireciona para login)
        if (!$user) {
            return $next($request);
        }

        // Admin (desenvolvedor) – acesso total, sem verificação de assinatura
        if ($user->isAdmin()) {
            return $next($request);
        }

        /** @var \App\Models\Barbershop|null $tenant */
        $tenant = Filament::getTenant();

        // Se não há tenant ativo na URL, nada a verificar
        if (!$tenant) {
            return $next($request);
        }

        // Verifica se o acesso está ativo (trial ou assinatura paga válida)
        if ($tenant->hasActiveAccess()) {
            return $next($request);
        }

        // --- Acesso bloqueado: assinatura expirada ---

        // Permite continuar para páginas de pagamento (evita loop infinito)
        $allowedRoutes = [
            'filament.admin.pages.billing',
            'filament.admin.pages.subscription',
        ];

        if ($request->routeIs(...$allowedRoutes)) {
            return $next($request);
        }

        // Redireciona para a página de cobrança com aviso
        return redirect()
            ->route('filament.admin.pages.billing', ['tenant' => $tenant->slug])
            ->with('warning', 'Sua assinatura expirou. Renove para continuar usando o sistema.');
    }
}