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
        Schema::create('barbers', function (Blueprint $table) {
            $table->id();
            // Vincula o barbeiro à barbearia (importante se o sistema tiver filiais no futuro)
            $table->foreignId('barbershop_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('email')->nullable(); // Opcional, caso queira dar acesso a ele depois
            $table->string('phone')->nullable();
            $table->string('avatar')->nullable();        // Foto do barbeiro
            $table->boolean('is_active')->default(true); // Para "demitir" ou dar férias sem apagar o histórico
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barbers');
    }
};
