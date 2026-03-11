<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Response;
use Throwable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Log detalhado caso debug ativado
            if (config('app.debug')) {
                \Log::error($e->getMessage(), [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            } else {
                // Em produção, logar apenas mensagem
                \Log::error('Erro na aplicação: ' . $e->getMessage());
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     * ✅ Retorna erros genéricos em produção para não expor detalhes internos
     */
    public function render($request, Throwable $exception)
    {
        // ✅ Erro 404 - Recurso não encontrado
        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'message' => 'Recurso não encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }

        // ✅ Erro 422 - Validação falhou
        if ($exception instanceof ValidationException) {
            return response()->json([
                'message' => 'Dados inválidos.',
                'errors' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // ✅ Erro 401 - Não autenticado
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'message' => 'Não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // ✅ Erro 403 - Não autorizado
        if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return response()->json([
                'message' => 'Não autorizado.',
            ], Response::HTTP_FORBIDDEN);
        }

        // ✅ Erro 429 - Rate limit excedido
        if ($exception instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException) {
            return response()->json([
                'message' => 'Muitas requisições. Tente novamente mais tarde.',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // ✅ Erros HTTP (400, 500, etc)
        if ($exception instanceof HttpException) {
            return response()->json([
                'message' => $exception->getMessage() ?: 'Ocorreu um erro ao processar sua solicitação.',
            ], $exception->getStatusCode());
        }

        // ✅ Em Produção: Logar e retornar mensagem genérica
        if (!config('app.debug')) {
            \Log::error('Erro não tratado: ' . get_class($exception), [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return response()->json([
                'message' => 'Ocorreu um erro no servidor. Tente novamente mais tarde.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return parent::render($request, $exception);
    }
}
