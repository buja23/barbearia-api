<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Retorna a mesma resposta independente de o email existir ou não
        // para evitar enumeração de usuários
        return response()->json([
            'message' => __('Se o email estiver cadastrado, você receberá um link de redefinição em breve.'),
        ]);
    }
}
