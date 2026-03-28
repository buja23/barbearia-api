<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Service;
use App\Models\Barber;
use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\OpeningHour;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 0. SEED DOS PLANOS SAAS DA PLATAFORMA
        $this->call(SaasPlanSeeder::class);

        // =====================================================================
        // 1. USUÁRIO ADMINISTRADOR
        // =====================================================================
        $dono = User::create([
            'name'              => env('ADMIN_NAME', 'Administrador'),
            'email'             => env('ADMIN_EMAIL', 'admin@barbearia.local'),
            'password'          => Hash::make(env('ADMIN_PASSWORD', 'ChangeMe@12345')),
            'role'              => 'admin',
            'email_verified_at' => now(),
        ]);

        // =====================================================================
        // 2. BARBEARIA PRINCIPAL (Barbearia do Victor)
        // =====================================================================
        $barbearia = Barbershop::create([
            'user_id'             => $dono->id,
            'name'                => 'Barbearia do Victor',
            'slug'                => 'barbearia-do-victor',
            'phone'               => '(11) 99999-9999',
            'address'             => 'Rua das Tesouras, 42 - Vila Nova',
            'subscription_status' => 'active',
            'trial_ends_at'       => now()->addDays(14),
        ]);

        // Horários de funcionamento (Seg–Sáb 09h–19h, Dom fechado)
        foreach (range(1, 6) as $dia) {
            OpeningHour::create([
                'barbershop_id' => $barbearia->id,
                'day_of_week'   => $dia,
                'opening_time'  => '09:00:00',
                'closing_time'  => '19:00:00',
                'is_closed'     => false,
            ]);
        }
        OpeningHour::create([
            'barbershop_id' => $barbearia->id,
            'day_of_week'   => 0,
            'opening_time'  => '09:00:00',
            'closing_time'  => '13:00:00',
            'is_closed'     => true,
        ]);

        // =====================================================================
        // 3. SERVIÇOS
        // =====================================================================
        $corte = Service::create([
            'barbershop_id'    => $barbearia->id,
            'name'             => 'Corte Social',
            'price'            => 35.00,
            'duration_minutes' => 30,
            'description'      => 'Corte tesoura e máquina.',
            'is_active'        => true,
        ]);
        $barba = Service::create([
            'barbershop_id'    => $barbearia->id,
            'name'             => 'Barba Modelada',
            'price'            => 25.00,
            'duration_minutes' => 20,
            'description'      => 'Toalha quente e navalha.',
            'is_active'        => true,
        ]);
        $completo = Service::create([
            'barbershop_id'    => $barbearia->id,
            'name'             => 'Cabelo + Barba',
            'price'            => 55.00,
            'duration_minutes' => 50,
            'description'      => 'Pacote completo.',
            'is_active'        => true,
        ]);
        $pigmentacao = Service::create([
            'barbershop_id'    => $barbearia->id,
            'name'             => 'Pigmentação',
            'price'            => 80.00,
            'duration_minutes' => 60,
            'description'      => 'Pigmentação capilar profissional.',
            'is_active'        => true,
        ]);

        // =====================================================================
        // 4. BARBEIROS
        // =====================================================================
        $barbeiro1 = Barber::create([
            'barbershop_id'         => $barbearia->id,
            'name'                  => 'João Navalha',
            'email'                 => 'joao@barbearia.com',
            'phone'                 => '(11) 99999-1111',
            'is_active'             => true,
            'commission_percentage' => 50.00,
            'lunch_start'           => '12:00:00',
            'lunch_end'             => '13:00:00',
        ]);
        $barbeiro2 = Barber::create([
            'barbershop_id'         => $barbearia->id,
            'name'                  => 'Pedro Tesoura',
            'email'                 => 'pedro@barbearia.com',
            'phone'                 => '(11) 99999-2222',
            'is_active'             => true,
            'commission_percentage' => 45.00,
            'lunch_start'           => '12:30:00',
            'lunch_end'             => '13:30:00',
        ]);

        // =====================================================================
        // 5. CLIENTES
        // =====================================================================
        $clientes = [
            ['name' => 'Carlos Silva',   'email' => 'carlos@teste.com'],
            ['name' => 'Ana Souza',      'email' => 'ana@teste.com'],
            ['name' => 'Marcos Lima',    'email' => 'marcos@teste.com'],
            ['name' => 'Bruno Costa',    'email' => 'bruno@teste.com'],
            ['name' => 'Rafael Mendes',  'email' => 'rafael@teste.com'],
        ];
        $users = [];
        foreach ($clientes as $c) {
            $users[] = User::create([
                'name'     => $c['name'],
                'email'    => $c['email'],
                'password' => Hash::make('123456'),
                'role'     => 'client',
                'email_verified_at' => now(),
            ]);
        }

        // =====================================================================
        // 6. AGENDAMENTOS — espalhados pelos últimos 3 meses + próximos 2 meses
        //    para testar a navegação do calendário
        // =====================================================================
        $servicos   = [$corte, $barba, $completo, $pigmentacao];
        $barbeiros  = [$barbeiro1, $barbeiro2];
        $statuses   = ['confirmed', 'confirmed', 'confirmed', 'pending', 'completed', 'canceled'];
        $horarios   = ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00'];

        // Gerar ~80 agendamentos distribuídos nos últimos 3 meses e próximos 2
        $usedSlots = [];
        $created   = 0;
        $target    = 80;
        $attempts  = 0;

        while ($created < $target && $attempts < 500) {
            $attempts++;
            $daysOffset = rand(-90, 60);
            $data       = Carbon::now()->addDays($daysOffset);

            // Pular domingos
            if ($data->dayOfWeek === 0) {
                continue;
            }

            $horario   = $horarios[array_rand($horarios)];
            $barb      = $barbeiros[array_rand($barbeiros)];
            $slotKey   = $barb->id . '_' . $data->format('Y-m-d') . '_' . $horario;

            if (isset($usedSlots[$slotKey])) {
                continue;
            }
            $usedSlots[$slotKey] = true;

            $servico   = $servicos[array_rand($servicos)];
            $cliente   = $users[array_rand($users)];
            $status    = $statuses[array_rand($statuses)];
            $scheduled = Carbon::parse($data->format('Y-m-d') . ' ' . $horario);

            Appointment::create([
                'barbershop_id'  => $barbearia->id,
                'barber_id'      => $barb->id,
                'service_id'     => $servico->id,
                'user_id'        => $cliente->id,
                'client_name'    => $cliente->name,
                'client_phone'   => '11999990000',
                'scheduled_at'   => $scheduled,
                'end_at'         => (clone $scheduled)->addMinutes($servico->duration_minutes),
                'total_price'    => $servico->price,
                'status'         => $status,
                'payment_status' => $status === 'completed' ? 'paid' : 'pending',
                'payment_method' => 'pix',
            ]);
            $created++;
        }

        // =====================================================================
        // 7. SEGUNDA BARBEARIA (dono diferente — para testar multi-tenant)
        // =====================================================================
        $dono2 = User::create([
            'name'              => 'Marina Andrade',
            'email'             => 'marina@barbearia2.com',
            'password'          => Hash::make('Senha@123'),
            'role'              => 'barber',
            'email_verified_at' => now(),
        ]);

        $barbearia2 = Barbershop::create([
            'user_id'             => $dono2->id,
            'name'                => 'Barbearia Estilo',
            'slug'                => 'barbearia-estilo',
            'phone'               => '(21) 98888-1234',
            'address'             => 'Av. Copacabana, 500 - Rio de Janeiro',
            'subscription_status' => 'trial',
            'trial_ends_at'       => now()->addDays(7),
        ]);

        foreach (range(1, 5) as $dia) {
            OpeningHour::create([
                'barbershop_id' => $barbearia2->id,
                'day_of_week'   => $dia,
                'opening_time'  => '10:00:00',
                'closing_time'  => '18:00:00',
                'is_closed'     => false,
            ]);
        }

        $corteBarbearia2 = Service::create([
            'barbershop_id'    => $barbearia2->id,
            'name'             => 'Corte Degradê',
            'price'            => 45.00,
            'duration_minutes' => 40,
            'is_active'        => true,
        ]);

        $barbeiro3 = Barber::create([
            'barbershop_id'         => $barbearia2->id,
            'name'                  => 'Lucas Fade',
            'email'                 => 'lucas@estilo.com',
            'phone'                 => '(21) 98888-5555',
            'is_active'             => true,
            'commission_percentage' => 55.00,
        ]);

        // Alguns agendamentos para a segunda barbearia
        foreach (range(1, 10) as $i) {
            $data = Carbon::now()->addDays(rand(-30, 30));
            if ($data->dayOfWeek === 0) continue;
            $horario   = $horarios[array_rand($horarios)];
            $cliente   = $users[array_rand($users)];
            $scheduled = Carbon::parse($data->format('Y-m-d') . ' ' . $horario);

            Appointment::create([
                'barbershop_id'  => $barbearia2->id,
                'barber_id'      => $barbeiro3->id,
                'service_id'     => $corteBarbearia2->id,
                'user_id'        => $cliente->id,
                'client_name'    => $cliente->name,
                'client_phone'   => '21988880000',
                'scheduled_at'   => $scheduled,
                'end_at'         => (clone $scheduled)->addMinutes(40),
                'total_price'    => 45.00,
                'status'         => 'confirmed',
                'payment_status' => 'pending',
                'payment_method' => 'pix',
            ]);
        }

        $this->command->info('✅ Seed completo!');
        $this->command->info('   Admin: ' . env('ADMIN_EMAIL', 'admin@barbearia.local'));
        $this->command->info('   Admin senha: ' . env('ADMIN_PASSWORD', 'ChangeMe@12345'));
        $this->command->info('   Barbearia: /admin/barbearia-do-victor/appointments');
        $this->command->info('   Agendamentos criados: ' . $created);
    }
}