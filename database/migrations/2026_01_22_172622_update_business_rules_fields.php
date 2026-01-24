<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    // Adiciona percentual de comissão ao barbeiro
    Schema::table('barbers', function (Blueprint $table) {
        $table->decimal('commission_percentage', 5, 2)->default(50.00);
    });

    // Adiciona limites ao plano e contador na assinatura
    Schema::table('plans', function (Blueprint $table) {
        $table->integer('monthly_limit')->default(4);
    });

    Schema::table('subscriptions', function (Blueprint $table) {
        $table->integer('uses_this_month')->default(0);
    });

    // Adiciona o valor da comissão calculada ao agendamento
    Schema::table('appointments', function (Blueprint $table) {
        $table->decimal('barber_commission_value', 10, 2)->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
