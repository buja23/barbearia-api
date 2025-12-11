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
    Schema::create('services', function (Blueprint $table) {
        $table->id();
        // Um serviço pertence a uma Barbearia específica
        $table->foreignId('barbershop_id')->constrained()->cascadeOnDelete();
        
        $table->string('name'); // Ex: "Corte Degradê"
        $table->decimal('price', 10, 2); // Ex: 35.00
        $table->integer('duration_minutes')->default(30); // Duração para calcular a agenda
        
        $table->text('description')->nullable();
        $table->boolean('is_active')->default(true); // Se o barbeiro parar de fazer, ele desativa
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
