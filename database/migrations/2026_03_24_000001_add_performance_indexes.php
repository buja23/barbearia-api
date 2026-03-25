<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Appointments: buscas frequentes por usuário e ordenação por data
        Schema::table('appointments', function (Blueprint $table) {
            $table->index('user_id', 'idx_appointments_user_id');
            $table->index('scheduled_at', 'idx_appointments_scheduled_at');
        });

        // Orders: busca no webhook por payment_id
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'payment_id')) {
                $table->index('payment_id', 'idx_orders_payment_id');
            }
        });

        // Services: filtro por barbearia
        Schema::table('services', function (Blueprint $table) {
            $table->index('barbershop_id', 'idx_services_barbershop_id');
        });

        // Barbers: filtro por barbearia
        Schema::table('barbers', function (Blueprint $table) {
            $table->index('barbershop_id', 'idx_barbers_barbershop_id');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appointments_user_id');
            $table->dropIndex('idx_appointments_scheduled_at');
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasIndex('orders', 'idx_orders_payment_id')) {
                $table->dropIndex('idx_orders_payment_id');
            }
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex('idx_services_barbershop_id');
        });

        Schema::table('barbers', function (Blueprint $table) {
            $table->dropIndex('idx_barbers_barbershop_id');
        });
    }
};
