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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            // Relacionamentos principais
            $table->foreignId('barber_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();

            // Cliente: Pode ser um usuário do sistema (app) ou um agendamento manual (balcão)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('client_name')->nullable(); // Para agendamentos manuais
            $table->string('client_phone')->nullable();

                                                    // Dados do Agendamento
            $table->dateTime('scheduled_at');       // Data e Hora do corte
            $table->dateTime('end_at')->nullable(); // Calculamos baseado na duração do serviço

                                                   // Financeiro
            $table->decimal('total_price', 10, 2); // Gravamos o preço DO MOMENTO (se o serviço aumentar depois, esse não muda)

                                                          // Controle
            $table->string('status')->default('pending'); // pending, confirmed, cancelled, completed
            $table->text('notes')->nullable();            // "Cliente pediu para não atrasar", etc.

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
