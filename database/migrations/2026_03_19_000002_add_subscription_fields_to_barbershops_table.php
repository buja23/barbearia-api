<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barbershops', function (Blueprint $table) {
            // Controle de assinatura SaaS ao nível do tenant (barbearia)
            $table->string('subscription_status')
                ->default('trial')
                ->after('logo_path')
                ->comment('trial | active | expired | cancelled');

            $table->timestamp('trial_ends_at')
                ->nullable()
                ->after('subscription_status')
                ->comment('Data de fim do período trial gratuito');

            $table->timestamp('subscription_expires_at')
                ->nullable()
                ->after('trial_ends_at')
                ->comment('Data de expiração da assinatura paga');

            $table->string('subscription_plan')->nullable()
                ->after('subscription_expires_at')
                ->comment('Nome do plano ativo: basic, pro, enterprise');
        });
    }

    public function down(): void
    {
        Schema::table('barbershops', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_status',
                'trial_ends_at',
                'subscription_expires_at',
                'subscription_plan',
            ]);
        });
    }
};
