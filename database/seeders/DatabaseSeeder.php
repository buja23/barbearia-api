<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Service;
use App\Models\Barber;
use App\Models\Appointment;
use App\Models\Barbershop; // <--- Importante!
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 0. SEED DOS PLANOS SAAS DA PLATAFORMA
        $this->call(SaasPlanSeeder::class);

        // 1. SEU LOGIN DE ADMINISTRADOR (DONO)
        // Pegue as credenciais do .env para NÃO expor dados reais no código
        $dono = User::create([
            'name'              => env('ADMIN_NAME', 'Administrador'),
            'email'             => env('ADMIN_EMAIL', 'admin@barbearia.local'),
            'password'          => Hash::make(env('ADMIN_PASSWORD', 'ChangeMe@12345')),
            'role'              => 'admin',
            'email_verified_at' => now(),
        ]);

        // 2. CRIAR A BARBEARIA DO VICTOR (Obrigatório para criar barbeiros/serviços)
        $barbearia = Barbershop::create([
            'user_id' => $dono->id,
            'name'    => 'Barbearia do Victor',
            'slug'    => 'barbearia-do-victor', // <--- Slug único
            'phone'   => '(11) 99999-9999',
            'address' => 'Rua das Nuvens, 100',
        ]);

        // 3. SERVIÇOS (Vinculados à barbearia)
        // Atenção: Usei 'duration_minutes' que é o nome correto no banco
        $corte = Service::create([
            'barbershop_id'    => $barbearia->id, // <--- VÍNCULO OBRIGATÓRIO
            'name'             => 'Corte Social',
            'price'            => 35.00,
            'duration_minutes' => 30,             // <--- Nome correto da coluna
            'description'      => 'Corte tesoura e máquina.',
        ]);

        $barba = Service::create([
            'barbershop_id'    => $barbearia->id,
            'name'             => 'Barba Modelada',
            'price'            => 25.00,
            'duration_minutes' => 20,
            'description'      => 'Toalha quente e navalha.',
        ]);

        $completo = Service::create([
            'barbershop_id'    => $barbearia->id,
            'name'             => 'Cabelo + Barba',
            'price'            => 50.00,
            'duration_minutes' => 50,
            'description'      => 'Pacote completo.',
        ]);

        // 4. BARBEIROS (Vinculados à barbearia)
        $barbeiro1 = Barber::create([
            'barbershop_id' => $barbearia->id, // <--- VÍNCULO OBRIGATÓRIO
            'name'          => 'João Navalha',
            'email'         => 'joao@barbearia.com',
            'phone'         => '(11) 99999-1111',
            'is_active'     => true,
        ]);

        $barbeiro2 = Barber::create([
            'barbershop_id' => $barbearia->id,
            'name'          => 'Pedro Tesoura',
            'email'         => 'pedro@barbearia.com',
            'phone'         => '(11) 99999-2222',
            'is_active'     => true,
        ]);

        // 5. CLIENTES
        $cliente1 = User::create([
            'name'     => 'Carlos Cliente',
            'email'    => 'carlos@teste.com',
            'password' => Hash::make('123456'),
            'role'     => 'client',
        ]);

        $cliente2 = User::create([
            'name'     => 'Ana Cliente',
            'email'    => 'ana@teste.com',
            'password' => Hash::make('123456'),
            'role'     => 'client',
        ]);

        // 6. AGENDAMENTOS
        Appointment::create([
            'user_id'        => $cliente1->id,
            'barber_id'      => $barbeiro1->id,
            'service_id'     => $corte->id,
            'client_name'    => $cliente1->name,
            'scheduled_at'   => Carbon::tomorrow()->setHour(14)->setMinute(0),
            'total_price'    => $corte->price,
            'status'         => 'pending',
            'payment_status' => 'pending',
        ]);

        Appointment::create([
            'user_id'        => $cliente2->id,
            'barber_id'      => $barbeiro2->id,
            'service_id'     => $completo->id,
            'client_name'    => $cliente2->name,
            'scheduled_at'   => Carbon::tomorrow()->setHour(16)->setMinute(0),
            'total_price'    => $completo->price,
            'status'         => 'confirmed',
            'payment_status' => 'pending',
        ]);
    }
}