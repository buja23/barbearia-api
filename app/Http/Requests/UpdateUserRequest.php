<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name'  => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $this->user()->id,
        ];

        // Troca de senha: exige senha atual + nova senha confirmada
        if ($this->filled('password')) {
            $rules['current_password'] = 'required|current_password';
            $rules['password'] = ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => 'A senha atual informada está incorreta.',
        ];
    }
}
