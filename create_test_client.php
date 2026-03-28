<?php

// Lista barbearias
$barbershops = App\Models\Barbershop::select('id', 'name', 'slug')->get();
echo "=== BARBEARIAS ===\n";
foreach ($barbershops as $b) {
    echo "ID: {$b->id} | Nome: {$b->name} | Slug: {$b->slug}\n";
}

// Lista planos
$plans = App\Models\Plan::select('id', 'barbershop_id', 'name', 'price', 'is_active')->get();
echo "\n=== PLANOS ===\n";
foreach ($plans as $p) {
    echo "ID: {$p->id} | Barbearia ID: {$p->barbershop_id} | Nome: {$p->name} | Preço: {$p->price} | Ativo: {$p->is_active}\n";
}

// Cria cliente de teste se não existir
$email = 'cliente.teste@barbearia.test';
$existing = App\Models\User::where('email', $email)->first();

if ($existing) {
    echo "\n=== CLIENTE JÁ EXISTE ===\n";
    echo "ID: {$existing->id} | Nome: {$existing->name} | Email: {$existing->email} | barbershop_id: {$existing->barbershop_id}\n";
} else {
    $barbershop = App\Models\Barbershop::first();
    if (!$barbershop) {
        echo "\nNenhuma barbearia encontrada. Crie uma barbearia primeiro.\n";
        return;
    }

    $client = App\Models\User::create([
        'name'         => 'Cliente Teste',
        'email'        => $email,
        'password'     => Hash::make('Senha@12345'),
        'role'         => 'client',
        'barbershop_id' => $barbershop->id,
    ]);

    echo "\n=== CLIENTE CRIADO ===\n";
    echo "ID: {$client->id}\n";
    echo "Nome: {$client->name}\n";
    echo "Email: {$client->email}\n";
    echo "Senha: Senha@12345\n";
    echo "barbershop_id: {$client->barbershop_id}\n";
}

// Cria assinatura de teste se houver plano disponível
$barbershop = App\Models\Barbershop::first();
if ($barbershop) {
    $plan = App\Models\Plan::where('barbershop_id', $barbershop->id)->where('is_active', true)->first();
    $client = App\Models\User::where('email', $email)->first();

    if ($plan && $client && !$client->activeSubscription) {
        $sub = App\Models\Subscription::create([
            'user_id'       => $client->id,
            'plan_id'       => $plan->id,
            'barbershop_id' => $plan->barbershop_id,
            'starts_at'     => now(),
            'expires_at'    => now()->addMonth(),
            'remaining_cuts' => $plan->cuts_per_month,
            'status'        => 'active',
            'uses_this_month' => 0,
        ]);
        echo "\n=== ASSINATURA CRIADA ===\n";
        echo "ID: {$sub->id} | Plano: {$plan->name} | Vence: {$sub->expires_at}\n";
    } elseif ($client && $client->activeSubscription) {
        $sub = $client->activeSubscription;
        echo "\n=== ASSINATURA EXISTENTE ===\n";
        echo "ID: {$sub->id} | Plano: {$sub->plan->name} | Status: {$sub->status}\n";
    }
}
