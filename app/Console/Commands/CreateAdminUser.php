<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create
                            {--name= : Nome do administrador}
                            {--email= : E-mail do administrador}
                            {--password= : Senha (mínimo 10 caracteres, maiúscula, minúscula e número)}';

    protected $description = 'Cria um usuário administrador do sistema';

    public function handle(): int
    {
        $this->info('=== Criar Administrador ===');

        // --- Coletar nome ---
        $name = $this->option('name') ?: $this->ask('Nome completo');

        // --- Coletar e-mail ---
        $email = $this->option('email') ?: $this->ask('E-mail');

        $emailValidation = Validator::make(['email' => $email], [
            'email' => 'required|email|unique:users,email',
        ]);

        if ($emailValidation->fails()) {
            $this->error('E-mail inválido ou já cadastrado: ' . implode(', ', $emailValidation->errors()->all()));
            return self::FAILURE;
        }

        // --- Coletar senha ---
        $password = $this->option('password') ?: $this->secret('Senha');

        $passwordValidation = Validator::make(['password' => $password], [
            'password' => [
                'required',
                'min:10',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            ],
        ], [
            'password.regex' => 'A senha deve conter letras maiúsculas, minúsculas e números.',
            'password.min'   => 'A senha deve ter no mínimo 10 caracteres.',
        ]);

        if ($passwordValidation->fails()) {
            $this->error(implode("\n", $passwordValidation->errors()->all()));
            return self::FAILURE;
        }

        // --- Confirmar criação ---
        $this->table(['Campo', 'Valor'], [
            ['Nome',  $name],
            ['E-mail', $email],
            ['Role',  'admin'],
        ]);

        if (! $this->confirm('Confirmar criação do administrador?', true)) {
            $this->line('Cancelado.');
            return self::SUCCESS;
        }

        // --- Criar usuário ---
        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
            'role'     => 'admin',
        ]);

        $this->info("✓ Administrador criado com sucesso! (ID: {$user->id})");
        $this->line("  Acesse: " . url('/admin'));

        return self::SUCCESS;
    }
}
