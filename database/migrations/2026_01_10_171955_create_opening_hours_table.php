<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opening_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barbershop_id')->constrained()->cascadeOnDelete();
            
            // 0 = Domingo, 1 = Segunda, ..., 6 = Sábado
            $table->unsignedTinyInteger('day_of_week');
            
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            
            // Caso a barbearia feche em um dia específico (ex: Domingo)
            $table->boolean('is_closed')->default(false);
            
            $table->timestamps();
            
            // Garante que não existam duplicatas de dia para a mesma barbearia
            $table->unique(['barbershop_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_hours');
    }
};