<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            // Senha: Mínimo 10 caracteres com letras, números e símbolos
            'password' => [
                'required',
                'string',
                'min:10',
                'confirmed',
                // Valida: letras maiúsculas, minúsculas, números e símbolos
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            ],
        ], [
            'password.regex' => 'A senha deve conter letras maiúsculas, minúsculas e números.',
            'password.min' => 'A senha deve ter no mínimo 10 caracteres.',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => 'client', // Força sempre como cliente
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user,
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // Verificar se o usuário existe E a senha está correta
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user,
        ]);
    }

    public function logout(Request $request)
    {
        // Revoga o token que está sendo usado na requisição atual
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout realizado com sucesso']);
    }

    public function update(UpdateUserRequest $request)
    {
        $user      = $request->user();
        $validated = $request->validated();

        // Se o email foi alterado, reseta verificação
        if ($user->email !== $validated['email']) {
            $validated['email_verified_at'] = null;
        }

        $user->update([
            'name'               => $validated['name'],
            'email'              => $validated['email'],
            'email_verified_at'  => $validated['email_verified_at'] ?? $user->email_verified_at,
        ]);

        return response()->json([
            'message' => 'Perfil atualizado com sucesso!',
            'user'    => $user,
        ]);
    }
}
