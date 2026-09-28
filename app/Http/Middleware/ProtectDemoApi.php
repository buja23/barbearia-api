<?php

namespace App\Http\Middleware;

use App\Models\Barber;
use App\Models\Barbershop;
use App\Models\Service;
use App\Models\User;
use App\Support\DemoAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectDemoApi
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! DemoAccess::enabled()) {
            return $next($request);
        }

        if ($request->is('api/password/forgot', 'api/password/reset')) {
            $user = User::where('email', (string) $request->input('email'))->first();

            if ($user && DemoAccess::isDemoUser($user)) {
                return response()->json(['message' => DemoAccess::MESSAGE], 403);
            }
        }

        $user = $request->user('sanctum');

        if (! $user || ! DemoAccess::isDemoUser($user)) {
            return $next($request);
        }

        if (! $request->isMethodSafe() && ! $request->is('api/login', 'api/logout')) {
            return response()->json(['message' => DemoAccess::MESSAGE], 403);
        }

        if ($slug = $request->route('slug')) {
            $tenant = Barbershop::where('slug', $slug)->first();

            if (! $tenant || ! $user->canAccessTenant($tenant)) {
                return response()->json(['message' => DemoAccess::MESSAGE], 403);
            }

            foreach (['barber_id' => Barber::class, 'service_id' => Service::class] as $field => $model) {
                if ($request->filled($field) && ! $model::query()->whereKey($request->input($field))
                    ->where('barbershop_id', $tenant->id)->exists()) {
                    return response()->json(['message' => DemoAccess::MESSAGE], 403);
                }
            }
        }

        return $next($request);
    }
}
