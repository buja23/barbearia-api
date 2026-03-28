<?php

namespace Database\Seeders;

use App\Models\Barbershop;
use App\Models\SaasPlan;
use Illuminate\Database\Seeder;

class SaasPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'        => 'Basico',
                'description' => 'Plano mensal para quem quer flexibilidade sem compromisso longo.',
                'price'       => 199.00,
                'billing_cycle_months' => 1,
                'features'    => [
                    'Pagamento mensal de R$ 199,00',
                    'Todos os recursos do sistema liberados',
                    'Ideal para começar com ciclo curto',
                    'Sem desconto por fidelidade',
                ],
                'is_active'   => true,
                'is_popular'  => false,
                'sort_order'  => 1,
            ],
            [
                'name'        => 'Pro',
                'description' => 'Plano semestral com desconto para reduzir o custo por mês.',
                'price'       => 1074.00,
                'billing_cycle_months' => 6,
                'features'    => [
                    'Pagamento semestral de R$ 1.074,00',
                    'Economia de 10% em relação ao plano mensal',
                    'Equivale a R$ 179,00 por mês',
                    'Todos os recursos do sistema liberados',
                ],
                'is_active'   => true,
                'is_popular'  => true,
                'sort_order'  => 2,
            ],
            [
                'name'        => 'Premium',
                'description' => 'Plano anual com o maior desconto para maximizar a economia.',
                'price'       => 1908.00,
                'billing_cycle_months' => 12,
                'features'    => [
                    'Pagamento anual de R$ 1.908,00',
                    'Economia de 20% em relação ao plano mensal',
                    'Equivale a R$ 159,00 por mês',
                    'Todos os recursos do sistema liberados',
                ],
                'is_active'   => true,
                'is_popular'  => false,
                'sort_order'  => 3,
            ],
        ];

        $legacyStarter = SaasPlan::whereIn('name', ['Starter', 'Básico'])->first();

        if ($legacyStarter) {
            $legacyStarter->update($plans[0]);
        } else {
            SaasPlan::updateOrCreate(['name' => $plans[0]['name']], $plans[0]);
        }

        SaasPlan::updateOrCreate(['name' => $plans[1]['name']], $plans[1]);
        SaasPlan::updateOrCreate(['name' => $plans[2]['name']], $plans[2]);

        SaasPlan::whereNotIn('name', array_column($plans, 'name'))
            ->update(['is_active' => false]);

        Barbershop::whereIn('subscription_plan', ['Starter', 'Básico'])
            ->update(['subscription_plan' => 'Basico']);
    }
}
