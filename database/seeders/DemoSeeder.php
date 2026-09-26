<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Barbershop;
use App\Models\OpeningHour;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // ================================================================
            // 1. CONTA DEMO
            // ================================================================

            $demo = User::updateOrCreate(
                [
                    'email' => env('DEMO_EMAIL', 'demo@barbearia.app'),
                ],
                [
                    'name' => 'Usuário Demo',
                    'password' => Hash::make(
                        env('DEMO_PASSWORD', 'Demo@12345')
                    ),
                    'role' => 'barber',
                    'email_verified_at' => now(),
                ]
            );

            // ================================================================
            // 2. BARBEARIA DEMO
            // ================================================================

            $barbershop = Barbershop::updateOrCreate(
                [
                    'slug' => 'barbearia-demo',
                ],
                [
                    'user_id' => $demo->id,
                    'name' => 'Barbearia Prime Demo',
                    'description' => 'Ambiente demonstrativo do sistema de gestão para barbearias.',
                    'phone' => '(18) 99999-0000',
                    'address' => 'Av. Brasil, 1000 - Centro',
                    'subscription_status' => 'active',
                    'subscription_plan' => 'premium',
                    'subscription_expires_at' => now()->addYears(10),
                    'trial_ends_at' => null,
                ]
            );

            $demo->update([
                'barbershop_id' => $barbershop->id,
            ]);

            // ================================================================
            // LIMPA SOMENTE OS DADOS DA BARBEARIA DEMO
            // Permite executar este seeder novamente sem duplicar tudo.
            // ================================================================

            $this->clearDemoData($barbershop);

            // ================================================================
            // CADASTROS
            // ================================================================

            $this->seedOpeningHours($barbershop);

            $services = $this->seedServices($barbershop);

            $barbers = $this->seedBarbers($barbershop);

            $clients = $this->seedClients($barbershop);

            $products = $this->seedProducts($barbershop);

            $plans = $this->seedPlans($barbershop);

            $this->seedSubscriptions(
                $barbershop,
                $plans,
                $clients
            );

            $this->seedAppointments(
                $barbershop,
                $services,
                $barbers,
                $clients
            );

            $this->seedOrders(
                $barbershop,
                $products
            );
        });

        $this->command->newLine();
        $this->command->info('============================================');
        $this->command->info('✅ CONTA DEMO CRIADA COM SUCESSO');
        $this->command->info('============================================');

        $this->command->info(
            'E-mail: ' . env('DEMO_EMAIL', 'demo@barbearia.app')
        );

        $this->command->info(
            'Senha: ' . env('DEMO_PASSWORD', 'Demo@12345')
        );

        $this->command->info(
            'Barbearia: Barbearia Prime Demo'
        );

        $this->command->info(
            'Slug: barbearia-demo'
        );
    }

    // ========================================================================
    // LIMPEZA
    // ========================================================================

    private function clearDemoData(Barbershop $barbershop): void
    {
        /*
         * Pedidos precisam excluir os itens primeiro.
         */
        $orderIds = Order::where(
            'barbershop_id',
            $barbershop->id
        )->pluck('id');

        if ($orderIds->isNotEmpty()) {
            OrderItem::whereIn('order_id', $orderIds)->delete();
        }

        Order::where(
            'barbershop_id',
            $barbershop->id
        )->delete();

        /*
         * Agendamentos.
         */
        Appointment::where(
            'barbershop_id',
            $barbershop->id
        )->delete();

        /*
         * Assinaturas dos clientes.
         */
        Subscription::where(
            'barbershop_id',
            $barbershop->id
        )->delete();
    }

    // ========================================================================
    // 3. HORÁRIOS
    // ========================================================================

    private function seedOpeningHours(
        Barbershop $barbershop
    ): void {
        OpeningHour::where(
            'barbershop_id',
            $barbershop->id
        )->delete();

        /*
         * Segunda a sábado: 09:00 às 19:00
         */
        foreach (range(1, 6) as $day) {
            OpeningHour::create([
                'barbershop_id' => $barbershop->id,
                'day_of_week' => $day,
                'opening_time' => '09:00:00',
                'closing_time' => '19:00:00',
                'is_closed' => false,
            ]);
        }

        /*
         * Domingo fechado.
         */
        OpeningHour::create([
            'barbershop_id' => $barbershop->id,
            'day_of_week' => 0,
            'opening_time' => '09:00:00',
            'closing_time' => '13:00:00',
            'is_closed' => true,
        ]);
    }

    // ========================================================================
    // 4. SERVIÇOS
    // ========================================================================

    private function seedServices(
        Barbershop $barbershop
    ): array {
        $data = [
            [
                'name' => 'Corte Tradicional',
                'price' => 40.00,
                'duration_minutes' => 30,
                'description' => 'Corte tradicional com máquina e tesoura.',
            ],
            [
                'name' => 'Corte Degradê',
                'price' => 50.00,
                'duration_minutes' => 40,
                'description' => 'Degradê com acabamento profissional.',
            ],
            [
                'name' => 'Barba Completa',
                'price' => 30.00,
                'duration_minutes' => 25,
                'description' => 'Barba com navalha e toalha quente.',
            ],
            [
                'name' => 'Cabelo + Barba',
                'price' => 70.00,
                'duration_minutes' => 60,
                'description' => 'Pacote completo de cabelo e barba.',
            ],
            [
                'name' => 'Pigmentação',
                'price' => 80.00,
                'duration_minutes' => 60,
                'description' => 'Pigmentação capilar profissional.',
            ],
            [
                'name' => 'Sobrancelha',
                'price' => 20.00,
                'duration_minutes' => 15,
                'description' => 'Design e acabamento de sobrancelha.',
            ],
        ];

        $services = [];

        foreach ($data as $serviceData) {
            $services[] = Service::updateOrCreate(
                [
                    'barbershop_id' => $barbershop->id,
                    'name' => $serviceData['name'],
                ],
                [
                    ...$serviceData,
                    'barbershop_id' => $barbershop->id,
                    'is_active' => true,
                ]
            );
        }

        return $services;
    }

    // ========================================================================
    // 5. BARBEIROS
    // ========================================================================

    private function seedBarbers(
        Barbershop $barbershop
    ): array {
        $data = [
            [
                'name' => 'Lucas Martins',
                'email' => 'lucas.demo@barbearia.app',
                'phone' => '(18) 99999-1001',
                'commission_percentage' => 50,
                'lunch_start' => '12:00:00',
                'lunch_end' => '13:00:00',
            ],
            [
                'name' => 'Gabriel Santos',
                'email' => 'gabriel.demo@barbearia.app',
                'phone' => '(18) 99999-1002',
                'commission_percentage' => 45,
                'lunch_start' => '12:30:00',
                'lunch_end' => '13:30:00',
            ],
            [
                'name' => 'Rafael Oliveira',
                'email' => 'rafael.demo@barbearia.app',
                'phone' => '(18) 99999-1003',
                'commission_percentage' => 50,
                'lunch_start' => '13:00:00',
                'lunch_end' => '14:00:00',
            ],
        ];

        $barbers = [];

        foreach ($data as $barberData) {
            $barbers[] = Barber::updateOrCreate(
                [
                    'barbershop_id' => $barbershop->id,
                    'email' => $barberData['email'],
                ],
                [
                    ...$barberData,
                    'barbershop_id' => $barbershop->id,
                    'is_active' => true,
                ]
            );
        }

        return $barbers;
    }

    // ========================================================================
    // 6. CLIENTES
    // ========================================================================

    private function seedClients(
        Barbershop $barbershop
    ): array {
        $data = [
            ['name' => 'Carlos Silva', 'email' => 'carlos.demo@teste.com'],
            ['name' => 'João Mendes', 'email' => 'joao.demo@teste.com'],
            ['name' => 'Pedro Almeida', 'email' => 'pedro.demo@teste.com'],
            ['name' => 'Matheus Costa', 'email' => 'matheus.demo@teste.com'],
            ['name' => 'Bruno Souza', 'email' => 'bruno.demo@teste.com'],
            ['name' => 'Felipe Rocha', 'email' => 'felipe.demo@teste.com'],
            ['name' => 'Lucas Ferreira', 'email' => 'lucas.cliente.demo@teste.com'],
            ['name' => 'André Martins', 'email' => 'andre.demo@teste.com'],
            ['name' => 'Diego Santos', 'email' => 'diego.demo@teste.com'],
            ['name' => 'Gustavo Lima', 'email' => 'gustavo.demo@teste.com'],
        ];

        $clients = [];

        foreach ($data as $index => $clientData) {
            $clients[] = User::updateOrCreate(
                [
                    'email' => $clientData['email'],
                ],
                [
                    'name' => $clientData['name'],
                    'password' => Hash::make('Cliente@12345'),
                    'role' => 'client',
                    'barbershop_id' => $barbershop->id,
                    'phone' => '1899999' . str_pad(
                        (string) ($index + 1),
                        4,
                        '0',
                        STR_PAD_LEFT
                    ),
                    'email_verified_at' => now(),
                ]
            );
        }

        return $clients;
    }

    // ========================================================================
    // 7. PRODUTOS
    // ========================================================================

private function seedProducts(
    Barbershop $barbershop
): array {
    $data = [
        [
            'name' => 'Pomada Modeladora Premium',
            'description' => 'Pomada de alta fixação com efeito seco.',
            'cost_price' => 18.00,
            'sale_price' => 35.00,
            'quantity' => 30,
            'min_stock_alert' => 5,
            'type' => 'resale',
        ],
        [
            'name' => 'Shampoo Masculino 300ml',
            'description' => 'Shampoo profissional para uso diário.',
            'cost_price' => 16.00,
            'sale_price' => 32.00,
            'quantity' => 25,
            'min_stock_alert' => 5,
            'type' => 'resale',
        ],
        [
            'name' => 'Óleo para Barba',
            'description' => 'Óleo hidratante para barba.',
            'cost_price' => 20.00,
            'sale_price' => 42.00,
            'quantity' => 18,
            'min_stock_alert' => 4,
            'type' => 'resale',
        ],
        [
            'name' => 'Balm para Barba',
            'description' => 'Balm hidratante e modelador.',
            'cost_price' => 17.00,
            'sale_price' => 38.00,
            'quantity' => 20,
            'min_stock_alert' => 4,
            'type' => 'resale',
        ],
        [
            'name' => 'Cera Modeladora',
            'description' => 'Cera de fixação média e acabamento natural.',
            'cost_price' => 15.00,
            'sale_price' => 30.00,
            'quantity' => 22,
            'min_stock_alert' => 5,
            'type' => 'resale',
        ],
        [
            'name' => 'Pós-Barba Premium',
            'description' => 'Loção refrescante pós-barba.',
            'cost_price' => 22.00,
            'sale_price' => 45.00,
            'quantity' => 15,
            'min_stock_alert' => 3,
            'type' => 'resale',
        ],

        // Produto de uso interno
        [
            'name' => 'Lâminas Profissionais',
            'description' => 'Lâminas utilizadas nos atendimentos da barbearia.',
            'cost_price' => 12.00,
            'sale_price' => 0,
            'quantity' => 50,
            'min_stock_alert' => 10,
            'type' => 'usage',
        ],
    ];

    $products = [];

    foreach ($data as $productData) {
        $products[] = Product::updateOrCreate(
            [
                'barbershop_id' => $barbershop->id,
                'name' => $productData['name'],
            ],
            [
                ...$productData,
                'barbershop_id' => $barbershop->id,
            ]
        );
    }

    return $products;
}

    // ========================================================================
    // 8. PLANOS DE CLIENTES
    // ========================================================================

    private function seedPlans(
        Barbershop $barbershop
    ): array {
        $data = [
            [
                'name' => 'Plano Essencial',
                'description' => '2 cortes por mês.',
                'price' => 69.90,
                'cuts_per_month' => 2,
            ],
            [
                'name' => 'Plano Premium',
                'description' => '4 cortes por mês.',
                'price' => 119.90,
                'cuts_per_month' => 4,
            ],
            [
                'name' => 'Plano VIP',
                'description' => '8 cortes por mês.',
                'price' => 199.90,
                'cuts_per_month' => 8,
            ],
        ];

        $plans = [];

        foreach ($data as $planData) {
            $plans[] = Plan::updateOrCreate(
                [
                    'barbershop_id' => $barbershop->id,
                    'name' => $planData['name'],
                ],
                [
                    ...$planData,
                    'barbershop_id' => $barbershop->id,
                    'is_active' => true,
                ]
            );
        }

        return $plans;
    }

    // ========================================================================
    // 9. ASSINATURAS
    // ========================================================================

    private function seedSubscriptions(
        Barbershop $barbershop,
        array $plans,
        array $clients
    ): void {
        /*
         * Cliente 0: Plano Essencial
         */
        Subscription::create([
            'user_id' => $clients[0]->id,
            'plan_id' => $plans[0]->id,
            'barbershop_id' => $barbershop->id,
            'starts_at' => now()->startOfMonth(),
            'expires_at' => now()->addMonth(),
            'remaining_cuts' => 1,
            'status' => 'active',
            'uses_this_month' => 1,
        ]);

        /*
         * Cliente 1: Premium
         */
        Subscription::create([
            'user_id' => $clients[1]->id,
            'plan_id' => $plans[1]->id,
            'barbershop_id' => $barbershop->id,
            'starts_at' => now()->startOfMonth(),
            'expires_at' => now()->addMonth(),
            'remaining_cuts' => 2,
            'status' => 'active',
            'uses_this_month' => 2,
        ]);

        /*
         * Cliente 2: VIP
         */
        Subscription::create([
            'user_id' => $clients[2]->id,
            'plan_id' => $plans[2]->id,
            'barbershop_id' => $barbershop->id,
            'starts_at' => now()->startOfMonth(),
            'expires_at' => now()->addMonth(),
            'remaining_cuts' => 6,
            'status' => 'active',
            'uses_this_month' => 2,
        ]);

        /*
         * Cliente 3: assinatura expirada.
         */
        Subscription::create([
            'user_id' => $clients[3]->id,
            'plan_id' => $plans[0]->id,
            'barbershop_id' => $barbershop->id,
            'starts_at' => now()->subMonths(2),
            'expires_at' => now()->subMonth(),
            'remaining_cuts' => 0,
            'status' => 'expired',
            'uses_this_month' => 2,
        ]);

        /*
         * Cliente 4: assinatura cancelada.
         */
        Subscription::create([
            'user_id' => $clients[4]->id,
            'plan_id' => $plans[1]->id,
            'barbershop_id' => $barbershop->id,
            'starts_at' => now()->subMonth(),
            'expires_at' => now()->addDays(15),
            'remaining_cuts' => 1,
            'status' => 'canceled',
            'uses_this_month' => 3,
        ]);
    }

    // ========================================================================
    // 10. AGENDAMENTOS
    // ========================================================================

    private function seedAppointments(
        Barbershop $barbershop,
        array $services,
        array $barbers,
        array $clients
    ): void {
        /*
         * Histórico dos últimos 3 meses.
         *
         * Valores determinísticos para o dashboard sempre ficar preenchido.
         */
        $pastOffsets = [
            -88, -83, -79, -74, -70, -66,
            -61, -57, -53, -48, -44, -40,
            -36, -32, -29, -26, -23, -20,
            -18, -16, -14, -12, -10, -8,
            -7, -6, -5, -4, -3, -2, -1,
        ];

        $times = [
            '09:00',
            '10:00',
            '11:00',
            '14:00',
            '15:00',
            '16:00',
            '17:00',
        ];

        foreach ($pastOffsets as $index => $daysOffset) {
            $date = now()->copy()->addDays($daysOffset);

            /*
             * Se cair domingo, joga para segunda.
             */
            if ($date->dayOfWeek === Carbon::SUNDAY) {
                $date->addDay();
            }

            $service = $services[$index % count($services)];
            $barber = $barbers[$index % count($barbers)];
            $client = $clients[$index % count($clients)];
            $time = $times[$index % count($times)];

            $scheduled = Carbon::parse(
                $date->format('Y-m-d') . ' ' . $time
            );

            /*
             * A maioria fica concluída para alimentar faturamento.
             */
            $status = $index % 8 === 0
                ? 'canceled'
                : 'completed';

            Appointment::create([
                'barbershop_id' => $barbershop->id,
                'barber_id' => $barber->id,
                'service_id' => $service->id,
                'user_id' => $client->id,
                'client_name' => $client->name,
                'client_phone' => '(18) 99999-2000',
                'scheduled_at' => $scheduled,
                'end_at' => $scheduled
                    ->copy()
                    ->addMinutes($service->duration_minutes),
                'total_price' => $service->price,
                'status' => $status,
                'payment_status' => $status === 'completed'
                    ? 'paid'
                    : 'pending',
                'payment_method' => 'pix',
                'barber_commission_value' => $status === 'completed'
                    ? (
                        (float) $service->price
                        * (float) $barber->commission_percentage
                    ) / 100
                    : null,
            ]);
        }

        /*
         * Agendamentos de hoje e futuros.
         */
        $futureAppointments = [
            [0, '09:00', 'confirmed'],
            [0, '10:00', 'confirmed'],
            [0, '14:00', 'pending'],
            [0, '16:00', 'confirmed'],

            [1, '09:00', 'confirmed'],
            [1, '10:00', 'pending'],
            [1, '14:00', 'confirmed'],
            [1, '15:00', 'confirmed'],
            [1, '17:00', 'confirmed'],

            [2, '09:00', 'confirmed'],
            [2, '11:00', 'confirmed'],
            [2, '14:00', 'pending'],
            [2, '16:00', 'confirmed'],

            [3, '10:00', 'confirmed'],
            [3, '15:00', 'confirmed'],

            [4, '09:00', 'pending'],
            [4, '11:00', 'confirmed'],
            [4, '16:00', 'confirmed'],

            [5, '10:00', 'confirmed'],
            [5, '14:00', 'confirmed'],

            [7, '09:00', 'confirmed'],
            [7, '11:00', 'pending'],
            [7, '15:00', 'confirmed'],

            [8, '10:00', 'confirmed'],
            [8, '16:00', 'confirmed'],

            [10, '09:00', 'confirmed'],
            [10, '14:00', 'confirmed'],

            [12, '11:00', 'confirmed'],
            [12, '16:00', 'pending'],
        ];

        foreach ($futureAppointments as $index => $item) {
            [$daysOffset, $time, $status] = $item;

            $date = now()->copy()->addDays($daysOffset);

            if ($date->dayOfWeek === Carbon::SUNDAY) {
                $date->addDay();
            }

            $service = $services[$index % count($services)];
            $barber = $barbers[$index % count($barbers)];
            $client = $clients[$index % count($clients)];

            $scheduled = Carbon::parse(
                $date->format('Y-m-d') . ' ' . $time
            );

            Appointment::create([
                'barbershop_id' => $barbershop->id,
                'barber_id' => $barber->id,
                'service_id' => $service->id,
                'user_id' => $client->id,
                'client_name' => $client->name,
                'client_phone' => '(18) 99999-3000',
                'scheduled_at' => $scheduled,
                'end_at' => $scheduled
                    ->copy()
                    ->addMinutes($service->duration_minutes),
                'total_price' => $service->price,
                'status' => $status,
                'payment_status' => 'pending',
                'payment_method' => 'pix',
            ]);
        }
    }

    // ========================================================================
    // 11. PEDIDOS / VENDAS
    // ========================================================================

    private function seedOrders(
        Barbershop $barbershop,
        array $products
    ): void {
        $orders = [
            [
                'days_ago' => 3,
                'status' => 'approved',
                'items' => [
                    [0, 1],
                    [2, 1],
                ],
            ],
            [
                'days_ago' => 7,
                'status' => 'approved',
                'items' => [
                    [1, 2],
                    [3, 1],
                ],
            ],
            [
                'days_ago' => 12,
                'status' => 'approved',
                'items' => [
                    [4, 1],
                    [5, 1],
                ],
            ],
            [
                'days_ago' => 20,
                'status' => 'approved',
                'items' => [
                    [0, 2],
                    [1, 1],
                ],
            ],
            [
                'days_ago' => 35,
                'status' => 'approved',
                'items' => [
                    [2, 1],
                    [3, 2],
                ],
            ],
            [
                'days_ago' => 50,
                'status' => 'approved',
                'items' => [
                    [0, 1],
                    [4, 2],
                ],
            ],
            [
                'days_ago' => 70,
                'status' => 'approved',
                'items' => [
                    [1, 1],
                    [5, 1],
                ],
            ],
            [
                'days_ago' => 2,
                'status' => 'pending',
                'items' => [
                    [2, 1],
                ],
            ],
        ];

        foreach ($orders as $orderData) {
            $total = 0;

            foreach ($orderData['items'] as [$productIndex, $quantity]) {
                $total += (
                    (float) $products[$productIndex]->sale_price
                    * $quantity
                );
            }

            $order = Order::create([
                'barbershop_id' => $barbershop->id,
                'total_amount' => $total,
                'status' => $orderData['status'],
                'payment_id' => null,
                'pix_copy_paste' => null,
                'qr_code_base64' => null,
            ]);

            /*
             * Ajusta created_at para os gráficos representarem
             * vários períodos.
             */
            $order->timestamps = false;

            $order->created_at = now()
                ->subDays($orderData['days_ago']);

            $order->updated_at = $order->created_at;

            $order->save();

            $order->timestamps = true;

            foreach ($orderData['items'] as [$productIndex, $quantity]) {
                $product = $products[$productIndex];

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->sale_price,
                    'cost_price' => $product->cost_price,
                ]);
            }

            /*
             * Para venda aprovada, aplica baixa real no estoque.
             */
            if ($order->status === 'approved') {
                $order->applyInventory();
            }
        }
    }
}