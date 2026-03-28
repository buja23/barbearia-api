<?php

namespace App\Console\Commands;

use App\Models\Barbershop;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestClient extends Command
{
    protected $signature   = 'dev:create-test-client';
    protected $description = 'Cria um cliente de teste com assinatura ativa para desenvolvimento.';

    public function handle(): int
    {
        $this->info('=== BARBEARIAS ===');
        $barbershops = Barbershop::all();

        if ($barbershops->isEmpty()) {
            $this->error('Nenhuma barbearia encontrada. Crie uma barbearia primeiro.');
            return self::FAILURE;
        }

        foreach ($barbershops as $b) {
            $this->line("ID: {$b->id} | Nome: {$b->name} | Slug: {$b->slug}");
        }

        $barbershopName = $this->choice(
            'Qual barbearia vincular ao cliente?',
            $barbershops->pluck('name')->all(),
            0
        );

        $barbershop = $barbershops->firstWhere('name', $barbershopName);

        // --- Cria ou reutiliza cliente ---
        $email  = 'cliente.teste@barbearia.test';
        $client = User::firstOrCreate(
            ['email' => $email],
            [
                'name'          => 'Cliente Teste',
                'password'      => Hash::make('Senha@12345'),
                'role'          => 'client',
                'barbershop_id' => $barbershop->id,
            ]
        );

        if ($client->wasRecentlyCreated) {
            $this->info("\n✅ Cliente criado:");
        } else {
            // Atualiza barbershop_id caso seja diferente
            $client->update(['barbershop_id' => $barbershop->id]);
            $this->warn("\n⚠️  Cliente já existia (barbershop_id atualizado):");
        }

        $this->table(['Campo', 'Valor'], [
            ['ID',           $client->id],
            ['Nome',         $client->name],
            ['Email',        $client->email],
            ['Senha',        'Senha@12345'],
            ['Barbearia',    $barbershop->name],
        ]);

        // --- Planos disponíveis ---
        $plans = Plan::where('barbershop_id', $barbershop->id)
            ->where('is_active', true)
            ->get();

        if ($plans->isEmpty()) {
            $this->warn("\n⚠️  Nenhum plano ativo encontrado para {$barbershop->name}. Crie um plano primeiro.");
            return self::SUCCESS;
        }

        $this->info("\n=== PLANOS DISPONÍVEIS ===");
        foreach ($plans as $p) {
            $this->line("ID: {$p->id} | {$p->name} | R$ {$p->price} | {$p->cuts_per_month} cortes/mês");
        }

        if (! $this->confirm('Criar assinatura ativa para o cliente?', true)) {
            return self::SUCCESS;
        }

        $planName = $this->choice(
            'Qual plano?',
            $plans->pluck('name')->all(),
            0
        );

        $plan = $plans->firstWhere('name', $planName);

        // Cancela assinaturas ativas anteriores
        Subscription::where('user_id', $client->id)
            ->where('status', 'active')
            ->update(['status' => 'canceled']);

        $subscription = Subscription::create([
            'user_id'         => $client->id,
            'plan_id'         => $plan->id,
            'barbershop_id'   => $barbershop->id,
            'starts_at'       => now(),
            'expires_at'      => now()->addMonth(),
            'remaining_cuts'  => $plan->cuts_per_month,
            'status'          => 'active',
            'uses_this_month' => 0,
        ]);

        $this->info("\n✅ Assinatura criada com sucesso!");
        $this->table(['Campo', 'Valor'], [
            ['ID',          $subscription->id],
            ['Plano',       $plan->name],
            ['Início',      $subscription->starts_at],
            ['Vencimento',  $subscription->expires_at],
            ['Cortes',      $subscription->remaining_cuts],
            ['Status',      $subscription->status],
        ]);

        $this->newLine();
        $this->info('💡 Use estas credenciais no app:');
        $this->line("   Email: {$client->email}");
        $this->line('   Senha: Senha@12345');

        return self::SUCCESS;
    }
}
