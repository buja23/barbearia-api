<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Barber;
use App\Models\Service;
use Carbon\Carbon;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Limpa a tabela para o teste ser limpo
        Appointment::query()->truncate(); // ou delete() se der erro de foreign key

        // 2. Garante que existem dados base
        $barber = Barber::first() ?? Barber::factory()->create(['name' => 'Barbeiro Teste']);
        $service = Service::first() ?? Service::factory()->create(['name' => 'Corte Degrade', 'price' => 50.00]);
        
        // Cria alguns clientes para variar os nomes na lista
        $clientes = User::where('role', 'client')->take(5)->get();
        if ($clientes->isEmpty()) {
            $clientes = User::factory(5)->create(['role' => 'client']);
        }

        $hoje = Carbon::today();

        // ====================================================================
        // CENÁRIO 1: HOJE -> DIA LOTADO (Vermelho, > 10 agendamentos)
        // ====================================================================
        $this->command->info('Criando dia LOTADO (Hoje)...');

        // 08:00 - Concluído e Pago (Verde / Approved)
        Appointment::create([
            'user_id' => $clientes[0]->id, 'barber_id' => $barber->id, 'service_id' => $service->id,
            'scheduled_at' => $hoje->copy()->setHour(8),
            'total_price' => $service->price,
            'status' => 'completed',
            'payment_status' => 'approved',
            'payment_method' => 'pix',
        ]);

        // 09:00 - Faltou (Vermelho / No-Show)
        Appointment::create([
            'user_id' => $clientes[1]->id, 'barber_id' => $barber->id, 'service_id' => $service->id,
            'scheduled_at' => $hoje->copy()->setHour(9),
            'total_price' => $service->price,
            'status' => 'no_show',
            'payment_status' => 'pending',
        ]);

        // 10:00 - Confirmado mas Não Pago (Azul / Pending Payment)
        Appointment::create([
            'user_id' => $clientes[2]->id, 'barber_id' => $barber->id, 'service_id' => $service->id,
            'scheduled_at' => $hoje->copy()->setHour(10),
            'total_price' => $service->price,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);

        // 11:00 - Pendente de Aprovação (Laranja)
        Appointment::create([
            'user_id' => $clientes[3]->id, 'barber_id' => $barber->id, 'service_id' => $service->id,
            'scheduled_at' => $hoje->copy()->setHour(11),
            'total_price' => $service->price,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        // Enche o resto do dia para bater 12 agendamentos (Lotação)
        for ($i = 13; $i <= 20; $i++) {
            Appointment::create([
                'user_id' => $clientes->random()->id,
                'barber_id' => $barber->id,
                'service_id' => $service->id,
                'scheduled_at' => $hoje->copy()->setHour($i),
                'total_price' => $service->price,
                'status' => 'confirmed', // Maioria confirmado
                'payment_status' => 'pending',
            ]);
        }

        // ====================================================================
        // CENÁRIO 2: AMANHÃ -> DIA MOVIMENTADO (Azul Escuro, ~6 agendamentos)
        // ====================================================================
        $amanha = Carbon::tomorrow();
        $this->command->info('Criando dia MOVIMENTADO (Amanhã)...');

        for ($i = 9; $i <= 14; $i++) {
            Appointment::create([
                'user_id' => $clientes->random()->id,
                'barber_id' => $barber->id,
                'service_id' => $service->id,
                'scheduled_at' => $amanha->copy()->setHour($i),
                'total_price' => $service->price,
                'status' => $i % 2 == 0 ? 'confirmed' : 'pending', // Alterna entre confirmado e pendente
                'payment_status' => 'pending',
            ]);
        }

        // ====================================================================
        // CENÁRIO 3: DEPOIS DE AMANHÃ -> TRANQUILO (Azul Claro, 1 agendamento)
        // ====================================================================
        $depoisAmanha = Carbon::today()->addDays(2);
        $this->command->info('Criando dia TRANQUILO (Depois de Amanhã)...');

        Appointment::create([
            'user_id' => $clientes[0]->id,
            'barber_id' => $barber->id,
            'service_id' => $service->id,
            'scheduled_at' => $depoisAmanha->copy()->setHour(15), // 15:00
            'total_price' => $service->price,
            'status' => 'confirmed',
            'payment_status' => 'approved', // Já pagou adiantado
        ]);

        $this->command->info('✅ Cenários de teste criados com sucesso!');
    }
}