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
    Schema::create('barbershops', function (Blueprint $table) {
        $table->id();
        // O Dono da barbearia
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('name');
        // O código do QR Code (ex: "barba-negra-123"). Tem que ser único.
        $table->string('slug')->unique(); 
        
        $table->string('phone')->nullable(); // Para o link do WhatsApp
        $table->string('address')->nullable();
        $table->string('logo_path')->nullable(); // Foto da barbearia
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barbershops');
    }
};
