<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // CONFIAR NO PROXY DO RENDER (Isto resolve o erro de JSON do Livewire/Filament)
        $middleware->trustProxies(at: '*');

        // Security headers em todas as respostas
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Sanitiza dados sensíveis dos logs (CPF, tokens, senhas)
        $middleware->append(\App\Http\Middleware\SanitizeLogging::class);

        // Registra o alias do middleware de assinatura SaaS
        $middleware->alias([
            'subscription.active' => \App\Http\Middleware\EnsureBusinessSubscriptionActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $exception, Request $request) {
            $forbidden = $exception instanceof \Illuminate\Auth\Access\AuthorizationException
                || ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                    && $exception->getStatusCode() === 403);

            if ($forbidden && \App\Support\DemoAccess::isDemoUser()) {
                return $request->expectsJson() || $request->is('api/*')
                    ? response()->json(['message' => \App\Support\DemoAccess::MESSAGE], 403)
                    : response(\App\Support\DemoAccess::MESSAGE, 403)
                        ->header('Content-Type', 'text/plain; charset=UTF-8');
            }

            return null;
        });

        // CONFIGURAÇÃO NOVA: Forçar JSON em erros da API
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            if ($request->is('api/*')) {
                return true;
            }
            return $request->expectsJson();
        });
    })->create();
