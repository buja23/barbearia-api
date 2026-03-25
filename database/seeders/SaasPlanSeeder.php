<?php

namespace Database\Seeders;

use App\Models\SaasPlan;
use Illuminate\Database\Seeder;

class SaasPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'        => 'Starter',
                'description' => 'Ideal para barbearias que estão começando.',
                'price'       => 0.01, // R$0,01 para testes — troque para o valor real antes de lançar
                'features'    => [
                    'Agendamentos ilimitados',
                    'Até 2 barbeiros',
                    'Link público de agendamento',
                    'QR Code da barbearia',
                    'Controle financeiro básico',
                    'Suporte por e-mail',
                ],
                'is_active'   => true,
                'is_popular'  => false,
                'sort_order'  => 1,
            ],
            [
                'name'        => 'Pro',
                'description' => 'Para barbearias que querem crescer com mais controle.',
                'price'       => 79.90,
                'features'    => [
                    'Agendamentos ilimitados',
                    'Barbeiros ilimitados',
                    'Link público de agendamento',
                    'QR Code da barbearia',
                    'Controle financeiro completo',
                    'Controle de estoque',
                    'Relatório de comissões',
                    'Dashboard com gráficos',
                    'Suporte prioritário',
                ],
                'is_active'   => true,
                'is_popular'  => true,
                'sort_order'  => 2,
            ],
            [
                'name'        => 'Premium',
                'description' => 'A experiência completa para barbearias de alto padrão.',
                'price'       => 149.90,
                'features'    => [
                    'Tudo do plano Pro',
                    'Múltiplas unidades',
                    'Planos de assinatura para clientes',
                    'Notificações automáticas por e-mail',
                    'Relatórios avançados',
                    'API para integração com apps',
                    'Onboarding personalizado',
                    'Suporte VIP via WhatsApp',
                ],
                'is_active'   => true,
                'is_popular'  => false,
                'sort_order'  => 3,
            ],
        ];

        foreach ($plans as $plan) {
            SaasPlan::firstOrCreate(['name' => $plan['name']], $plan);
        }
    }
}
