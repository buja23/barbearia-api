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
        // 1. Limpa a tabela de agendamentos para começar do zero
        Appointment::query()->delete();

        $barber = Barber::first();
        $service = Service::first();
        $user = User::where('role', 'client')->first() ?? User::factory()->create(['role' => 'client']);

        if (!$barber || !$service) {
            $this->command->error('Você precisa ter pelo menos 1 Barbeiro e 1 Serviço cadastrados!');
            return;
        }

        // 2. CRIA O CENÁRIO DE HOJE (Dia Movimentado)
        $hoje = Carbon::today();
        
        // 09:00 - Concluído e Pago (Pix)
        Appointment::create([
            'user_id' => $user->id,
            'barber_id' => $barber->id,
            'service_id' => $service->id,
            'scheduled_at' => $hoje->copy()->setHour(9),
            'end_at' => $hoje->copy()->setHour(10),
            'total_price' => $service->price,
            'barber_commission_value' => ($service->price * $barber->commission_rate) / 100,
            'status' => 'completed',
            'payment_method' => 'pix',
        ]);

        // 10:00 - Cliente Faltou (No Show)
        Appointment::create([
            'user_id' => $user->id,
            'barber_id' => $barber->id,
            'service_id' => $service->id,
            'scheduled_at' => $hoje->copy()->setHour(10),
            'end_at' => $hoje->copy()->setHour(11),
            'total_price' => $service->price,
            'status' => 'no_show', // Status novo que vamos usar
        ]);

        // 14:00 - Confirmado (Vai vir a tarde)
        Appointment::create([
            'user_id' => $user->id,
            'barber_id' => $barber->id,
            'service_id' => $service->id,
            'scheduled_at' => $hoje->copy()->setHour(14),
            'end_at' => $hoje->copy()->setHour(15),
            'total_price' => $service->price,
            'status' => 'confirmed',
        ]);

        // 15:00 - Pendente (Ainda não confirmou)
        Appointment::create([
            'user_id' => $user->id,
            'barber_id' => $barber->id,
            'service_id' => $service->id,
            'scheduled_at' => $hoje->copy()->setHour(15),
            'end_at' => $hoje->copy()->setHour(16),
            'total_price' => $service->price,
            'status' => 'pending',
        ]);

        // 3. CRIA O PASSADO (Histórico)
        Appointment::create([
            'user_id' => $user->id,
            'barber_id' => $barber->id,
            'service_id' => $service->id,
            'scheduled_at' => $hoje->copy()->subDays(2)->setHour(10), // 2 dias atrás
            'end_at' => $hoje->copy()->subDays(2)->setHour(11),
            'total_price' => $service->price,
            'status' => 'completed',
        ]);

        // 4. CRIA O FUTURO (Amanhã)
        Appointment::create([
            'user_id' => $user->id,
            'barber_id' => $barber->id,
            'service_id' => $service->id,
            'scheduled_at' => $hoje->copy()->addDay()->setHour(10), // Amanhã
            'end_at' => $hoje->copy()->addDay()->setHour(11),
            'total_price' => $service->price,
            'status' => 'confirmed',
        ]);

        $this->command->info('Agenda resetada e populada com sucesso! 🚀');
    }
}